<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManualReconciliationEntryResource\Pages;
use App\Models\ManualReconciliationEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManualReconciliationEntryResource extends Resource
{
    protected static ?string $model = ManualReconciliationEntry::class;

    protected static ?string $navigationGroup = 'Mutabakat Yönetimi';
    protected static ?string $navigationLabel = 'Manuel Mutabakat Girişleri';
    protected static ?string $pluralLabel = 'Manuel Mutabakat Girişleri';
    protected static ?string $slug = 'manual-reconciliation-entries';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Firma')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required()
                    ->placeholder('Firma ara...'),

                Forms\Components\TextInput::make('bank_name')
                    ->label('Banka Adı')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('branch_name')
                    ->label('Şube Adı')
                    ->maxLength(255),

                Forms\Components\TextInput::make('year')
                    ->label('Yıl')
                    ->numeric()
                    ->required()
                    ->minValue(2020)
                    ->maxValue(now()->year + 1)
                    ->default(now()->year),

                Forms\Components\DatePicker::make('requested_at')
                    ->label('Talep Tarihi (Firma bankaya ne zaman yazdı)')
                    ->displayFormat('d.m.Y')
                    ->native(false),

                Forms\Components\DatePicker::make('reply_received_at')
                    ->label('Banka Dönüş Tarihi')
                    ->displayFormat('d.m.Y')
                    ->native(false),

                Forms\Components\Textarea::make('notes')
                    ->label('Not')
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->label('Firma')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bank_name')->label('Banka')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('branch_name')->label('Şube')->placeholder('-'),
                Tables\Columns\TextColumn::make('year')->label('Yıl')->sortable(),
                Tables\Columns\TextColumn::make('requested_at')->label('Talep Tarihi')->date('d.m.Y')->placeholder('-'),
                Tables\Columns\TextColumn::make('reply_received_at')->label('Banka Dönüşü')->date('d.m.Y')->placeholder('-'),
            ])
            ->defaultSort('reply_received_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Firma')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('year')
                    ->form([Forms\Components\TextInput::make('year')->numeric()])
                    ->query(fn (Builder $q, array $data) => $q->when($data['year'] ?? null, fn ($q, $y) => $q->where('year', $y))),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManualReconciliationEntries::route('/'),
            'create' => Pages\CreateManualReconciliationEntry::route('/create'),
            'edit' => Pages\EditManualReconciliationEntry::route('/{record}/edit'),
        ];
    }
}
