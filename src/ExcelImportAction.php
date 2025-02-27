<?php

namespace EightyNine\ExcelImport;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;

class ExcelImportAction extends Action
{
    protected string $importClass = DefaultImport::class;

    protected array $importClassAttributes = [];

    protected ?string $disk = null;

    protected string | Closure $visibility = 'public';

    public function use(string $class = null, ...$attributes): static
    {
        $this->importClass = $class ?: DefaultImport::class;
        $this->importClassAttributes = $attributes;

        return $this;
    }

    protected function getDisk()
    {
        return $this->disk ?: config('filesystems.default');
    }

    public function visibility(string | Closure | null $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public static function getDefaultName(): ?string
    {
        return 'import';
    }

    public function action(Closure | string | null $action): static
    {
        if ($action !== 'importData') {
            throw new \Exception('You\'re unable to override the action for this plugin');
        }

        $this->action = $this->importData();

        return $this;
    }

    protected function getDefaultForm(): array
    {
        return [
            FileUpload::make('upload')
                ->label(function ($livewire) {
                    if (! method_exists($livewire, 'getTable')) {
                        return __('Excel Data');
                    }

                    return str($livewire->getTable()->getPluralModelLabel())->title() . ' ' . __('Excel Data');
                })
                ->default(1)
                ->disk($this->getDisk())
                ->visibility($this->visibility)
                ->columns()
                ->required(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-arrow-down-tray')
            ->color('warning')
            ->form($this->getDefaultForm())
            ->modalIcon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->modalWidth('md')
            ->modalAlignment('center')
            ->modalHeading(fn ($livewire) => __('Import Excel'))
            ->modalDescription(__('Import data into database from excel file'))
            ->modalFooterActionsAlignment('right')
            ->closeModalByClickingAway(false)
            ->action('importData');
    }

    /**
     * Import data function.
     *
     * @param  array  $data The data to import.
     * @param $livewire The Livewire instance.
     * @return bool Returns true if the import was successful, false otherwise.
     */
    private function importData(): Closure
    {
        return function (array $data, $livewire): bool {
            $importObject = new $this->importClass(
                method_exists($livewire, 'getModel') ? $livewire->getModel() : null,
                ...$this->importClassAttributes
            );
            Excel::import($importObject, $data['upload']);

            return true;
        };
    }
}
