<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeBilesenCommand extends Command
{
    protected $signature = 'make:bilesen';
    protected $description = 'Özelleştirilmiş bir bileşen ve Livewire komponentleri oluşturur';

    public function handle()
    {
        // Bileşen adını sor
        $name = $this->ask('Bileşen adını girin (ör. Sehir)');
        $model = Str::studly($name);
        $table = Str::snake(Str::plural($name));
        $modelFolder = $model; // Ör. 'Sehir'

        // ID veya UUID sor
        $primaryKeyType = $this->choice('Primary key türü ne olsun?', ['id', 'uuid'], 0);

        // Soft Deletes sor
        $useSoftDeletes = $this->confirm('Soft Deletes kullanılsın mı?', false);

        // Sütunları sor
        $columns = [];
        $columnTypes = [
            'string',
            'integer',
            'bigint',
            'text',
            'date',
            'datetime',
            'boolean',
            'uuid',
            'foreignId',
            'foreignUuid',
        ];

        while (true) {
            $columnName = $this->ask('Sütun adını girin (bitirmek için boş bırakın)');
            if (empty($columnName)) {
                break;
            }

            $columnType = $this->choice('Sütun tipi nedir?', $columnTypes, 0);
            $isRequired = $this->confirm("{$columnName} sütunu zorunlu (required) olsun mu?", true);

            $columns[] = [
                'name' => Str::snake($columnName), // Sütun adlarını snake_case yapalım
                'type' => $columnType,
                'required' => $isRequired,
            ];
        }

        if (empty($columns)) {
            $this->error('En az bir sütun tanımlamalısınız!');
            return;
        }

        // Migration oluştur
        $this->createMigration($model, $table, $columns, $primaryKeyType, $useSoftDeletes);

        // Model oluştur
        $this->createModel($model, $table, $columns, $primaryKeyType, $useSoftDeletes);

        // Livewire bileşenlerini oluştur
        $this->createLivewireComponents($model, $table, $columns, $modelFolder);

        $this->info("Bileşen '{$model}' başarıyla oluşturuldu!");
        $this->comment("Şimdi 'php artisan migrate' komutunu çalıştırabilirsiniz.");
    }

    protected function createMigration($model, $table, $columns, $primaryKeyType, $useSoftDeletes)
    {
        $timestamp = now()->format('Y_m_d_His');
        $migrationName = "create_{$table}_table";
        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");

        $migrationContent = "<?php\n\n";
        $migrationContent .= "use Illuminate\\Database\\Migrations\\Migration;\n";
        $migrationContent .= "use Illuminate\\Database\\Schema\\Blueprint;\n";
        $migrationContent .= "use Illuminate\\Support\\Facades\\Schema;\n\n";
        $migrationContent .= "return new class extends Migration\n";
        $migrationContent .= "{\n";
        $migrationContent .= "    public function up(): void\n";
        $migrationContent .= "    {\n";
        $migrationContent .= "        Schema::create('{$table}', function (Blueprint \$table) {\n";
        if ($primaryKeyType === 'uuid') {
            $migrationContent .= "            \$table->uuid('id')->primary();\n";
        } else {
            $migrationContent .= "            \$table->id();\n";
        }

        foreach ($columns as $column) {
            $columnName = $column['name'];
            $columnType = $column['type'];
            $isRequired = $column['required'] ? '' : '->nullable()';

            $line = "            \$table->{$columnType}('{$columnName}')";

            // Boolean için default değer ekleyelim
            if ($columnType === 'boolean') {
                $line .= "->default(false)";
            }

            $line .= "{$isRequired};\n";

            // Foreign key'ler için daha standart bir yöntem
            if ($columnType === 'foreignId' || $columnType === 'foreignUuid') {
                $relatedTable = Str::plural(Str::before($columnName, '_id'));
                $line = "            \$table->{$columnType}('{$columnName}'){$isRequired}->constrained('{$relatedTable}')->onDelete('cascade');\n";
            }

            $migrationContent .= $line;
        }

        if ($useSoftDeletes) {
            $migrationContent .= "            \$table->softDeletes();\n";
        }

        $migrationContent .= "            \$table->timestamps();\n";
        $migrationContent .= "        });\n";
        $migrationContent .= "    }\n\n";
        $migrationContent .= "    public function down(): void\n";
        $migrationContent .= "    {\n";
        $migrationContent .= "        Schema::dropIfExists('{$table}');\n";
        $migrationContent .= "    }\n";
        $migrationContent .= "};\n";

        File::ensureDirectoryExists(dirname($migrationPath));
        if (!File::put($migrationPath, $migrationContent)) {
            $this->error("Hata: {$migrationPath} dosyası oluşturulamadı!");
        }
    }

    protected function createModel($model, $table, $columns, $primaryKeyType, $useSoftDeletes)
    {
        $modelPath = app_path("Models/{$model}.php");
        $modelContent = "<?php\n\n";
        $modelContent .= "namespace App\\Models;\n\n";
        $modelContent .= "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n";
        $modelContent .= "use Illuminate\\Database\\Eloquent\\Model;\n";
        if ($primaryKeyType === 'uuid') {
            $modelContent .= "use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;\n";
        }
        if ($useSoftDeletes) {
            $modelContent .= "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n";
        }
        $modelContent .= "\nclass {$model} extends Model\n";
        $modelContent .= "{\n";
        $modelContent .= "    use HasFactory;\n";
        if ($primaryKeyType === 'uuid') {
            $modelContent .= "    use HasUuids;\n";
        }
        if ($useSoftDeletes) {
            $modelContent .= "    use SoftDeletes;\n";
        }

        $modelContent .= "\n    protected \$table = '{$table}';\n";

        $modelContent .= "\n    protected \$fillable = [" . implode(', ', array_map(function ($col) {
            return "'{$col['name']}'";
        }, $columns)) . "];\n";

        if ($primaryKeyType === 'uuid') {
            $modelContent .= "\n    public function getIncrementing(): bool\n";
            $modelContent .= "    {\n";
            $modelContent .= "        return false;\n";
            $modelContent .= "    }\n\n";
            $modelContent .= "    public function getKeyType(): string\n";
            $modelContent .= "    {\n";
            $modelContent .= "        return 'string';\n";
            $modelContent .= "    }\n";
        }
        $modelContent .= "}\n";

        File::ensureDirectoryExists(dirname($modelPath));
        if (!File::put($modelPath, $modelContent)) {
            $this->error("Hata: {$modelPath} dosyası oluşturulamadı!");
        }
    }

    protected function createLivewireComponents($model, $table, $columns, $modelFolder)
    {
        $this->createAnasayfaComponent($model, $table, $modelFolder);
        $this->createTabloComponent($model, $table, $columns, $modelFolder);
        $this->createEklemeModalComponent($model, $table, $columns, $modelFolder);
        $this->createDuzenlemeModalComponent($model, $table, $columns, $modelFolder);
    }

    protected function createAnasayfaComponent($model, $table, $modelFolder)
    {
        $className = "{$model}Anasayfa";
        $kebabClassName = Str::kebab($className);
        $kebabFolder = Str::kebab($modelFolder);

        // DEĞİŞİKLİK: Livewire yolu güncellendi (Http kaldırıldı).
        $componentPath = app_path("Livewire/{$modelFolder}/{$className}.php");
        $viewPath = resource_path("views/livewire/{$kebabFolder}/{$kebabClassName}.blade.php");

        // DEĞİŞİKLİK: Namespace, dosya yoluyla uyumlu hale getirildi.
        $componentContent = "<?php\n\n";
        $componentContent .= "namespace App\\Livewire\\{$modelFolder};\n\n";
        $componentContent .= "use Livewire\\Component;\n\n";
        $componentContent .= "class {$className} extends Component\n";
        $componentContent .= "{\n";
        $componentContent .= "    public \$showEklemeModal = false;\n";
        $componentContent .= "    public \$showDuzenlemeModal = false;\n";
        $componentContent .= "    public \$selectedItemId;\n\n";
        $componentContent .= "    protected \$listeners = [\n";
        $componentContent .= "        'closeModals' => 'closeAllModals',\n";
        $componentContent .= "        'openDuzenlemeModal' => 'openDuzenlemeModal',\n";
        $componentContent .= "    ];\n\n";
        $componentContent .= "    public function openDuzenlemeModal(\$itemId)\n";
        $componentContent .= "    {\n";
        $componentContent .= "        \$this->selectedItemId = \$itemId;\n";
        $componentContent .= "        \$this->showDuzenlemeModal = true;\n";
        $componentContent .= "    }\n\n";
        $componentContent .= "    public function closeAllModals()\n";
        $componentContent .= "    {\n";
        $componentContent .= "        \$this->showEklemeModal = false;\n";
        $componentContent .= "        \$this->showDuzenlemeModal = false;\n";
        $componentContent .= "    }\n\n";
        $componentContent .= "    public function render()\n";
        $componentContent .= "    {\n";
        $componentContent .= "        return view('livewire.{$kebabFolder}.{$kebabClassName}');\n";
        $componentContent .= "    }\n";
        $componentContent .= "}\n";

        // DEĞİŞİKLİK: Livewire tag'leri standart formata getirildi.
        $tabloTag = "livewire:{$kebabFolder}." . Str::kebab("{$model}Tablo");
        $eklemeModalTag = "livewire:{$kebabFolder}." . Str::kebab("{$model}EklemeModal");
        $duzenlemeModalTag = "livewire:{$kebabFolder}." . Str::kebab("{$model}DuzenlemeModal");

        $viewContent = "<div>\n";
        $viewContent .= "    <h1 class=\"text-2xl font-bold mb-4\">{$model} Yönetimi</h1>\n";
        $viewContent .= "    <button wire:click=\"\$set('showEklemeModal', true)\" class=\"mb-4 px-4 py-2 bg-blue-500 text-white rounded\">Yeni {$model} Ekle</button>\n\n";
        $viewContent .= "    <{$tabloTag} />\n\n";
        $viewContent .= "    @if(\$showEklemeModal)\n";
        $viewContent .= "        <{$eklemeModalTag} />\n";
        $viewContent .= "    @endif\n\n";
        $viewContent .= "    @if(\$showDuzenlemeModal)\n";
        $viewContent .= "        <{$duzenlemeModalTag} itemId=\"{\$selectedItemId}\" />\n";
        $viewContent .= "    @endif\n";
        $viewContent .= "</div>\n";

        $this->createFile($componentPath, $componentContent);
        $this->createFile($viewPath, $viewContent);
    }

    protected function createTabloComponent($model, $table, $columns, $modelFolder)
    {
        $className = "{$model}Tablo";
        $kebabClassName = Str::kebab($className);
        $kebabFolder = Str::kebab($modelFolder);

        // DEĞİŞİKLİK: Livewire yolu güncellendi (Http kaldırıldı).
        $componentPath = app_path("Livewire/{$modelFolder}/{$className}.php");
        $viewPath = resource_path("views/livewire/{$kebabFolder}/{$kebabClassName}.blade.php");

        $componentContent = "<?php\n\n";
        $componentContent .= "namespace App\\Livewire\\{$modelFolder};\n\n";
        $componentContent .= "use Livewire\\Component;\n";
        $componentContent .= "use Livewire\\WithPagination;\n";
        $componentContent .= "use App\\Models\\{$model};\n\n";
        $componentContent .= "class {$className} extends Component\n";
        $componentContent .= "{\n";
        $componentContent .= "    use WithPagination;\n\n";
        $componentContent .= "    public \$perPage = 10;\n";
        $componentContent .= "    public \$sortField = 'id';\n";
        $componentContent .= "    public \$sortDirection = 'asc';\n";
        $componentContent .= "    public \$search = '';\n\n";
        $componentContent .= "    protected \$listeners = ['refreshTable' => '$' . 'refresh'];\n\n";
        $componentContent .= "    public function sortBy(\$field)\n";
        $componentContent .= "    {\n";
        $componentContent .= "        if (\$this->sortField === \$field) {\n";
        $componentContent .= "            \$this->sortDirection = \$this->sortDirection === 'asc' ? 'desc' : 'asc';\n";
        $componentContent .= "        } else {\n";
        $componentContent .= "            \$this->sortDirection = 'asc';\n";
        $componentContent .= "        }\n";
        $componentContent .= "        \$this->sortField = \$field;\n";
        $componentContent .= "    }\n\n";
        $componentContent .= "    public function edit(\$id)\n";
        $componentContent .= "    {\n";
        $componentContent .= "        \$this->emitUp('openDuzenlemeModal', \$id);\n";
        $componentContent .= "    }\n\n";
        $componentContent .= "    public function delete(\$id)\n";
        $componentContent .= "    {\n";
        $componentContent .= "        {$model}::find(\$id)->delete();\n";
        $componentContent .= "    }\n\n";
        $componentContent .= "    public function render()\n";
        $componentContent .= "    {\n";
        $componentContent .= "        \$query = {$model}::query();\n\n";
        $componentContent .= "        if (\$this->search) {\n";
        $componentContent .= "            \$query->where(function(\$q) {\n";
        foreach ($columns as $column) {
            if (in_array($column['type'], ['string', 'text'])) {
                $componentContent .= "                \$q->orWhere('{$column['name']}', 'like', '%' . \$this->search . '%');\n";
            }
        }
        $componentContent .= "            });\n";
        $componentContent .= "        }\n\n";
        $componentContent .= "        \$items = \$query->orderBy(\$this->sortField, \$this->sortDirection)->paginate(\$this->perPage);\n";
        $componentContent .= "        return view('livewire.{$kebabFolder}.{$kebabClassName}', ['items' => \$items]);\n";
        $componentContent .= "    }\n";
        $componentContent .= "}\n";

        $viewContent = "<div class=\"overflow-x-auto\">\n";
        $viewContent .= "    <div class=\"mb-4\">\n";
        $viewContent .= "        <input type=\"text\" wire:model.debounce.300ms=\"search\" placeholder=\"Ara...\" class=\"w-full p-2 border border-gray-300 rounded\">\n";
        $viewContent .= "    </div>\n";
        $viewContent .= "    <table class=\"min-w-full bg-white border border-gray-300\">\n";
        $viewContent .= "        <thead>\n";
        $viewContent .= "            <tr>\n";
        $viewContent .= "                <th wire:click=\"sortBy('id')\" class=\"px-4 py-2 text-left border-b cursor-pointer\">ID <span class=\"text-xs\">@if(\$sortField === 'id') {{ \$sortDirection === 'asc' ? '▲' : '▼' }} @endif</span></th>\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $viewContent .= "                <th wire:click=\"sortBy('{$columnName}')\" class=\"px-4 py-2 text-left border-b cursor-pointer\">" . Str::title(str_replace('_', ' ', $columnName)) . " <span class=\"text-xs\">@if(\$sortField === '{$columnName}') {{ \$sortDirection === 'asc' ? '▲' : '▼' }} @endif</span></th>\n";
        }
        $viewContent .= "                <th class=\"px-4 py-2 text-left border-b\">İşlemler</th>\n";
        $viewContent .= "            </tr>\n";
        $viewContent .= "        </thead>\n";
        $viewContent .= "        <tbody>\n";
        $viewContent .= "            @forelse(\$items as \$item)\n";
        $viewContent .= "                <tr class=\"hover:bg-gray-100\">\n";
        $viewContent .= "                    <td class=\"px-4 py-2 border-b\">{{ \$item->id }}</td>\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $viewContent .= "                    <td class=\"px-4 py-2 border-b\">{{ \$item->$columnName }}</td>\n";
        }
        $viewContent .= "                    <td class=\"px-4 py-2 border-b\">\n";
        $viewContent .= "                        <button wire:click=\"edit('{{ \$item->id }}')\" class=\"text-blue-500 hover:underline\">Düzenle</button>\n";
        $viewContent .= "                        <button wire:click=\"delete('{{ \$item->id }}')\" onclick=\"return confirm('Bu kaydı silmek istediğinizden emin misiniz?')\" class=\"text-red-500 hover:underline ml-2\">Sil</button>\n";
        $viewContent .= "                    </td>\n";
        $viewContent .= "                </tr>\n";
        $viewContent .= "            @empty\n";
        $viewContent .= "                 <tr><td colspan=\"" . (count($columns) + 2) . "\" class=\"text-center p-4\">Kayıt bulunamadı.</td></tr>\n";
        $viewContent .= "            @endforelse\n";
        $viewContent .= "        </tbody>\n";
        $viewContent .= "    </table>\n";
        $viewContent .= "    <div class=\"mt-4\">{{ \$items->links() }}</div>\n";
        $viewContent .= "</div>\n";

        $this->createFile($componentPath, $componentContent);
        $this->createFile($viewPath, $viewContent);
    }

    protected function createEklemeModalComponent($model, $table, $columns, $modelFolder)
    {
        $className = "{$model}EklemeModal";
        $kebabClassName = Str::kebab($className);
        $kebabFolder = Str::kebab($modelFolder);

        // DEĞİŞİKLİK: Livewire yolu güncellendi (Http kaldırıldı).
        $componentPath = app_path("Livewire/{$modelFolder}/{$className}.php");
        $viewPath = resource_path("views/livewire/{$kebabFolder}/{$kebabClassName}.blade.php");

        $componentContent = "<?php\n\n";
        $componentContent .= "namespace App\\Livewire\\{$modelFolder};\n\n";
        $componentContent .= "use Livewire\\Component;\n";
        $componentContent .= "use App\\Models\\{$model};\n\n";
        $componentContent .= "class {$className} extends Component\n";
        $componentContent .= "{\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $defaultValue = $column['type'] === 'boolean' ? 'false' : "''";
            $componentContent .= "    public \${$columnName} = {$defaultValue};\n";
        }
        $componentContent .= "\n    protected function rules()\n    {\n        return [\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $rule = $column['required'] ? 'required' : 'nullable';
            $rule .= match ($column['type']) {
                'integer', 'bigint' => '|integer',
                'boolean' => '|boolean',
                'date', 'datetime' => '|date',
                'uuid', 'foreignUuid' => '|uuid',
                default => '|string|max:255',
            };
            $componentContent .= "            '{$columnName}' => '{$rule}',\n";
        }
        $componentContent .= "        ];\n    }\n\n";
        $componentContent .= "    public function save()\n";
        $componentContent .= "    {\n";
        $componentContent .= "        \$validatedData = \$this->validate();\n";
        $componentContent .= "        {$model}::create(\$validatedData);\n";
        $componentContent .= "        \$this->emitUp('closeModals');\n";
        $componentContent .= "        \$this->emitTo('{$kebabFolder}." . Str::kebab("{$model}Tablo") . "', 'refreshTable');\n";
        $componentContent .= "    }\n\n";
        $componentContent .= "    public function render()\n";
        $componentContent .= "    {\n";
        $componentContent .= "        return view('livewire.{$kebabFolder}.{$kebabClassName}');\n";
        $componentContent .= "    }\n";
        $componentContent .= "}\n";

        $viewContent = "<div class=\"fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50\">\n";
        $viewContent .= "    <div class=\"bg-white rounded-lg shadow-lg p-6 w-full max-w-md\">\n";
        $viewContent .= "        <h2 class=\"text-lg font-semibold mb-4\">Yeni {$model} Ekle</h2>\n";
        $viewContent .= "        <form wire:submit.prevent=\"save\">\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $inputType = match ($column['type']) {
                'boolean' => 'checkbox',
                'integer', 'bigint' => 'number',
                'date' => 'date',
                'datetime' => 'datetime-local',
                'text' => 'textarea',
                default => 'text',
            };
            $viewContent .= "            <div class=\"mb-4\">\n";
            $viewContent .= "                <label for=\"{$columnName}\" class=\"block text-sm font-medium text-gray-700\">" . Str::title(str_replace('_', ' ', $columnName)) . "</label>\n";
            if ($inputType === 'textarea') {
                $viewContent .= "                <textarea wire:model.defer=\"{$columnName}\" id=\"{$columnName}\" class=\"mt-1 block w-full border border-gray-300 rounded-md p-2\"></textarea>\n";
            } else {
                $viewContent .= "                <input type=\"{$inputType}\" wire:model.defer=\"{$columnName}\" id=\"{$columnName}\" class=\"mt-1 block w-full border border-gray-300 rounded-md p-2\" />\n";
            }
            $viewContent .= "                @error('{$columnName}') <span class=\"text-red-500 text-sm\">{{ \$message }}</span> @enderror\n";
            $viewContent .= "            </div>\n";
        }
        $viewContent .= "            <div class=\"mt-4 flex justify-end space-x-2\">\n";
        $viewContent .= "                <button type=\"button\" wire:click=\"\$emitUp('closeModals')\" class=\"px-4 py-2 bg-gray-200 rounded\">İptal</button>\n";
        $viewContent .= "                <button type=\"submit\" class=\"px-4 py-2 bg-blue-500 text-white rounded\">Kaydet</button>\n";
        $viewContent .= "            </div>\n";
        $viewContent .= "        </form>\n";
        $viewContent .= "    </div>\n";
        $viewContent .= "</div>\n";

        $this->createFile($componentPath, $componentContent);
        $this->createFile($viewPath, $viewContent);
    }

    protected function createDuzenlemeModalComponent($model, $table, $columns, $modelFolder)
    {
        $className = "{$model}DuzenlemeModal";
        $kebabClassName = Str::kebab($className);
        $kebabFolder = Str::kebab($modelFolder);

        // DEĞİŞİKLİK: Livewire yolu güncellendi (Http kaldırıldı).
        $componentPath = app_path("Livewire/{$modelFolder}/{$className}.php");
        $viewPath = resource_path("views/livewire/{$kebabFolder}/{$kebabClassName}.blade.php");

        $componentContent = "<?php\n\n";
        $componentContent .= "namespace App\\Livewire\\{$modelFolder};\n\n";
        $componentContent .= "use Livewire\\Component;\n";
        $componentContent .= "use App\\Models\\{$model};\n\n";
        $componentContent .= "class {$className} extends Component\n";
        $componentContent .= "{\n";
        $componentContent .= "    public \$itemId;\n";
        $componentContent .= "    public {$model} \$item;\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $componentContent .= "    public \${$columnName};\n";
        }
        $componentContent .= "\n    public function mount(\$itemId)\n";
        $componentContent .= "    {\n";
        $componentContent .= "        \$this->itemId = \$itemId;\n";
        $componentContent .= "        \$this->item = {$model}::find(\$this->itemId);\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $componentContent .= "        \$this->{$columnName} = \$this->item->{$columnName};\n";
        }
        $componentContent .= "    }\n\n";
        $componentContent .= "    protected function rules()\n    {\n        return [\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $rule = $column['required'] ? 'required' : 'nullable';
            $rule .= match ($column['type']) {
                'integer', 'bigint' => '|integer',
                'boolean' => '|boolean',
                'date', 'datetime' => '|date',
                'uuid', 'foreignUuid' => '|uuid',
                default => '|string|max:255',
            };
            $componentContent .= "            '{$columnName}' => '{$rule}',\n";
        }
        $componentContent .= "        ];\n    }\n\n";
        $componentContent .= "    public function save()\n";
        $componentContent .= "    {\n";
        $componentContent .= "        \$validatedData = \$this->validate();\n";
        $componentContent .= "        \$this->item->update(\$validatedData);\n";
        $componentContent .= "        \$this->emitUp('closeModals');\n";
        $componentContent .= "        \$this->emitTo('{$kebabFolder}." . Str::kebab("{$model}Tablo") . "', 'refreshTable');\n";
        $componentContent .= "    }\n\n";
        $componentContent .= "    public function render()\n";
        $componentContent .= "    {\n";
        $componentContent .= "        return view('livewire.{$kebabFolder}.{$kebabClassName}');\n";
        $componentContent .= "    }\n";
        $componentContent .= "}\n";

        $viewContent = "<div class=\"fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50\">\n";
        $viewContent .= "    <div class=\"bg-white rounded-lg shadow-lg p-6 w-full max-w-md\">\n";
        $viewContent .= "        <h2 class=\"text-lg font-semibold mb-4\">{$model} Düzenle</h2>\n";
        $viewContent .= "        <form wire:submit.prevent=\"save\">\n";
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $inputType = match ($column['type']) {
                'boolean' => 'checkbox',
                'integer', 'bigint' => 'number',
                'date' => 'date',
                'datetime' => 'datetime-local',
                'text' => 'textarea',
                default => 'text',
            };
            $viewContent .= "            <div class=\"mb-4\">\n";
            $viewContent .= "                <label for=\"edit-{$columnName}\" class=\"block text-sm font-medium text-gray-700\">" . Str::title(str_replace('_', ' ', $columnName)) . "</label>\n";
            if ($inputType === 'textarea') {
                $viewContent .= "                <textarea wire:model.defer=\"{$columnName}\" id=\"edit-{$columnName}\" class=\"mt-1 block w-full border border-gray-300 rounded-md p-2\"></textarea>\n";
            } else {
                $viewContent .= "                <input type=\"{$inputType}\" wire:model.defer=\"{$columnName}\" id=\"edit-{$columnName}\" class=\"mt-1 block w-full border border-gray-300 rounded-md p-2\" />\n";
            }
            $viewContent .= "                @error('{$columnName}') <span class=\"text-red-500 text-sm\">{{ \$message }}</span> @enderror\n";
            $viewContent .= "            </div>\n";
        }
        $viewContent .= "            <div class=\"mt-4 flex justify-end space-x-2\">\n";
        $viewContent .= "                <button type=\"button\" wire:click=\"\$emitUp('closeModals')\" class=\"px-4 py-2 bg-gray-200 rounded\">İptal</button>\n";
        $viewContent .= "                <button type=\"submit\" class=\"px-4 py-2 bg-blue-500 text-white rounded\">Kaydet</button>\n";
        $viewContent .= "            </div>\n";
        $viewContent .= "        </form>\n";
        $viewContent .= "    </div>\n";
        $viewContent .= "</div>\n";

        $this->createFile($componentPath, $componentContent);
        $this->createFile($viewPath, $viewContent);
    }

    // Helper function to create files and directories
    protected function createFile($path, $content)
    {
        File::ensureDirectoryExists(dirname($path));
        if (!File::put($path, $content)) {
            $this->error("Hata: {$path} dosyası oluşturulamadı!");
        }
    }
}
