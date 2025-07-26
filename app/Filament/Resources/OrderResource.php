<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $pluralModelLabel = 'Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('order_number')
                    ->required()
                    ->maxLength(50)
                    ->unique(Order::class, 'order_number', ignoreRecord: true),
                    
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                Select::make('status')
                    ->options(Order::getStatuses())
                    ->required(),
                    
                TextInput::make('total_price')
                    ->numeric()
                    ->required()
                    ->step(0.01),
                    
                Select::make('country_code')
                    ->label('Country')
                    ->options([
                        'MY' => 'Malaysia',
                        'SG' => 'Singapore',
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('shipping_address_display')
                    ->label('Shipping Address')
                    ->formatStateUsing(function ($record) {
                        if (!$record || !$record->shipping_address) return 'No address provided';
                        
                        $address = $record->shipping_address;
                        return "Name: {$address['name']}\nPhone: {$address['phone']}\nAddress: {$address['address']}";
                    })
                    ->rows(4)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'primary', 
                        'processing' => 'info',
                        'shipped' => 'gray',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),
                    
                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('MYR')
                    ->sortable(),
                    
                TextColumn::make('formatted_total_price')
                    ->label('Formatted Total')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('country_code')
                    ->label('Country')
                    ->sortable(),
                    
                TextColumn::make('total_quantity')
                    ->label('Items')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                    
                TextColumn::make('shipping_address.name')
                    ->label('Ship To')
                    ->getStateUsing(function ($record) {
                        return $record->shipping_address['name'] ?? 'No address';
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Order::getStatuses()),
                    
                SelectFilter::make('country_code')
                    ->label('Country')
                    ->options([
                        'MY' => 'Malaysia',
                        'SG' => 'Singapore',
                    ]),
                    
                SelectFilter::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['user:id,name,email']); // 预加载用户数据
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
