<?php

namespace App\Filament\Resources\Posts;

use App\Models\Post;
use App\Rules\DuplicateRule;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    /**
     * متد کمکی برای دریافت سایزها بر اساس دسته‌بندی انتخاب‌شده
     */
    protected static function getSizeOptions(?string $category): array
    {
        return match ($category) {
            'shoes' => [
                '36' => '36',
                '37' => '37',
                '38' => '38',
                '39' => '39',
                '40' => '40',
                '41' => '41',
                '42' => '42',
                '43' => '43',
                '44' => '44',
            ],
            'accessories' => [
                'Free Size' => 'تک سایز / فری سایز',
            ],
            default => [ // پوشاک (men, women و غیره)
                'S'   => 'S',
                'M'   => 'M',
                'L'   => 'L',
                'XL'  => 'XL',
                '2XL' => '2XL',
            ],
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            // اطلاعات پایه محصول
            Section::make('اطلاعات کلی محصول')
                ->schema([
                    TextInput::make('title')->label('نام محصول')->required(),
                    Select::make('category')
                        ->label('دسته‌بندی')
                        ->options([
                            'men'         => 'مردانه',
                            'women'       => 'زنانه',
                            'shoes'       => 'کفش',
                            'accessories' => 'اکسسوری',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('temp_sizes', [])),

                    TextInput::make('default_price')->label('قیمت پایه')->numeric()->prefix('تومان')->required(),
                    FileUpload::make('picture')->directory('posts')->image()->label('عکس محصول')->required()->columnSpanFull(),
                    Textarea::make('content')->label('توضیحات')->required()->columnSpanFull(),
                ])
                ->columns(3),

            // بخش ساخت خودکار تنوع‌ها
            Section::make('ساخت خودکار تنوع‌ها')
                ->description('اینجا می‌توانید رنگ، سایز، قیمت و موجودی را تعیین کنید.')
                ->schema([
                    Select::make('temp_color')
                        ->label('رنگ')
                        ->options([
                            'red'   => 'قرمز',
                            'blue'  => 'آبی',
                            'green' => 'سبز',
                            'black' => 'مشکی',
                            'white' => 'سفید',
                            'gold'  => 'طلایی',
                            'silver'=> 'نقره‌ای',
                        ]),

                    CheckboxList::make('temp_sizes')
                        ->label('سایزها  ')
                        ->options(fn (callable $get) => static::getSizeOptions($get('category')))
                        ->columns(4),

                    TextInput::make('temp_price')
                        ->label('قیمت برای همه')
                        ->numeric()
                        ->prefix('تومان'),

                    TextInput::make('temp_stock')
                        ->label('موجودی برای همه')
                        ->numeric(),

                    Actions::make([
                        Action::make('generate')
                            ->label('ساخت کارت‌ها')
                            ->action(function (callable $get, callable $set) {
                                $color = $get('temp_color');
                                $sizes = $get('temp_sizes') ?? [];
                                $price = $get('temp_price');
                                $stock = $get('temp_stock');

                                $variants = [];

                                foreach ($sizes as $size) {
                                    $variants[] = [
                                        'color' => $color,
                                        'size'  => $size,
                                        'price' => $price,
                                        'stock' => $stock,
                                    ];
                                }

                                $set('variants', array_merge($get('variants') ?? [], $variants));
                                $set('temp_color', null);
                                $set('temp_sizes', []);
                                $set('temp_price', null);
                                $set('temp_stock', null);
                            })
                    ])->columnSpanFull()
                ])
                ->columns(2)
                ->collapsible(),

            // جدول تنوع‌ها (Repeater)
            Repeater::make('variants')
                ->relationship('variants')
                ->label('ترکیب رنگ و سایز')
                ->schema([
                    Select::make('color')
                        ->label('رنگ')
                        ->options([
                            'red'    => 'قرمز',
                            'blue'   => 'آبی',
                            'green'  => 'سبز',
                            'black'  => 'مشکی',
                            'white'  => 'سفید',
                            'gold'   => 'طلایی',
                            'silver' => 'نقره‌ای',
                        ])
                        ->required(),

                    Select::make('size')
                        ->label('سایز / حافظه')
                        ->options(fn (callable $get) => static::getSizeOptions($get('../../category')))
                        ->required(),

                    TextInput::make('price')
                        ->label('قیمت')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('تومان'),

                    TextInput::make('stock')
                        ->label('موجودی')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(0),
                ])
                ->columns(2)
                ->createItemButtonLabel('افزودن ترکیب جدید')
                ->collapsible()
                ->rules([
                    new DuplicateRule(),
                ])
                ->columnSpanFull(),

            // بخش تنظیمات تخفیف به صورت تمیز و یکپارچه
            Section::make('تنظیمات تخفیف')
                ->schema([
                    Checkbox::make('has_discount')
                        ->label('این محصول تخفیف دارد')
                        ->default(false)
                        ->reactive()
                        ->columnSpanFull(),

                    Group::make([
                        TextInput::make('discount_percent')
                            ->label('درصد تخفیف')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),

                        DatePicker::make('discount_start')
                            ->label('تاریخ شروع تخفیف'),

                        DatePicker::make('discount_end')
                            ->label('تاریخ پایان تخفیف'),

                        Textarea::make('discount_note')
                            ->label('توضیحات تخفیف')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (callable $get) => (bool) $get('has_discount')),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                ImageColumn::make('picture')
                    ->label('تصویر محصول')
                    ->square()
                    ->size(60),

                TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record?->title)
                    ->sortable(),

                TextColumn::make('category')
                    ->label('دسته‌بندی')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('default_price')
                    ->label('قیمت')
                    ->money('IRR', true)
                    ->sortable(),

                TextColumn::make('discount_percent')
                    ->label('درصد تخفیف')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('discount_start')
                    ->label('شروع تخفیف')
                    ->date(),

                TextColumn::make('discount_end')
                    ->label('پایان تخفیف')
                    ->date(),

                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('has_discount')
                    ->label('فقط تخفیف‌دارها')
                    ->query(fn ($query) => $query->where('has_discount', true)),
            ])
            ->defaultSort('id', 'desc');
    }
}
