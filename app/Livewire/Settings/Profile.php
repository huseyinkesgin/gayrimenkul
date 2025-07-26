<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Models\Resim;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Str;

class Profile extends Component
{
    use WithFileUploads;
    
    public string $name = '';
    public string $email = '';
    public $photo;
    public $currentAvatar = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->loadCurrentAvatar();
    }
    
    /**
     * Load the current avatar if exists
     */
    public function loadCurrentAvatar(): void
    {
        $user = Auth::user();
        $avatar = $user->avatar;
        
        if ($avatar) {
            $this->currentAvatar = $avatar->url;
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'photo' => ['nullable', 'image', 'max:1024'], // 1MB max
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        
        // Handle photo upload if provided
        if ($this->photo) {
            $this->uploadAvatar($user);
        }

        $this->dispatch('profile-updated', name: $user->name);
    }
    
    /**
     * Upload and save the avatar
     */
    protected function uploadAvatar(User $user): void
    {
        try {
            // Generate a unique filename
            $filename = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
            
            // Store the file in the public disk under avatars directory
            $path = $this->photo->storeAs('avatars', $filename, 'public');
            
            // Get the full URL for the stored file
            $url = Storage::disk('public')->url($path);
            
            // Deactivate any existing avatars
            if ($user->avatar) {
                $user->avatar->update(['aktif_mi' => false]);
            }
            
            // Create a new Resim record
            $resim = new Resim([
                'url' => $url,
                'aktif_mi' => true,
            ]);
            
            // Associate with the user and save
            $user->avatar()->save($resim);
            
            // Update the current avatar
            $this->currentAvatar = $url;
            
            // Clear the photo property
            $this->photo = null;
            
            // Add a success message
            session()->flash('message', 'Profil resmi başarıyla güncellendi.');
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Avatar upload failed: ' . $e->getMessage());
            
            // Add an error message
            session()->flash('error', 'Profil resmi yüklenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Remove the current avatar
     */
    public function removeAvatar(): void
    {
        $user = Auth::user();
        
        if ($user->avatar) {
            // Deactivate the current avatar
            $user->avatar->update(['aktif_mi' => false]);
            
            // Update the current avatar display
            $this->currentAvatar = null;
            
            // Add success message
            session()->flash('message', 'Profil resmi başarıyla kaldırıldı.');
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
