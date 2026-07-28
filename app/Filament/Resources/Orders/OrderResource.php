<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages;
use App\Models\Order;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    public static function getNavigationLabel(): string
    {
        return 'سفارشات';
    }

    public static function getModelLabel(): string
    {
        return 'سفارش';
    }

    public static function getPluralModelLabel(): string
    {
        return 'سفارشات';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('اطلاعات کلی سفارش')
                ->schema([
                    TextInput::make('receiver_name')
                        ->label('نام گیرنده')
                        ->required(),

                    TextInput::make('phone')
                        ->label('شماره تماس')
                        ->required(),

                    Select::make('status')
                        ->label('وضعیت سفارش')
                        ->options([
                            'pending'    => 'در انتظار پرداخت',
                            'processing' => 'در حال پردازش',
                            'shipped'    => 'ارسال شده',
                            'delivered'  => 'تحویل شده',
                            'cancelled'  => 'لغو شده',
                        ])
                        ->required(),

                    TextInput::make('total_price')
                        ->label('مبلغ کل (تومان)')
                        ->numeric()
                        ->disabled(),

                    Textarea::make('address')
                        ->label('آدرس دقیق')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('اقلام سفارش')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('post_id')
                                ->label('نام محصول')
                                ->relationship('post', 'title')
                                ->searchable()
                                ->disabled()
                                ->columnSpan(2), // فضای کافی برای عنوان کامل محصول

                            TextInput::make('color')
                                ->label('رنگ')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('size')
                                ->label('سایز')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('quantity')
                                ->label('تعداد')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('price')
                                ->label('قیمت واحد')
                                ->prefix('تومان')
                                ->disabled()
                                ->columnSpan(1),
                        ])
                        ->columns(6) // تعداد ستون‌ها به ۶ افزایش یافت تا محصول جای بیشتری داشته باشد
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه سفارش')
                    ->sortable(),

                TextColumn::make('receiver_name')
                    ->label('گیرنده')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('شماره تماس')
                    ->searchable(),

                TextColumn::make('total_price')
                    ->label('مبلغ کل')
                    ->money('IRR', true)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'    => 'در انتظار',
                        'processing' => 'در حال پردازش',
                        'shipped'    => 'ارسال شده',
                        'delivered'  => 'تحویل شده',
                        'cancelled'  => 'لغو شده',
                        default      => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('فیلتر وضعیت')
                    ->options([
                        'pending'    => 'در انتظار پرداخت',
                        'processing' => 'در حال پردازش',
                        'shipped'    => 'ارسال شده',
                        'delivered'  => 'تحویل شده',
                        'cancelled'  => 'لغو شده',
                    ]),
            ])
            ->actions([
                ViewAction::make()->label('مشاهده'),
                EditAction::make()->label('ویرایش / وضعیت'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
