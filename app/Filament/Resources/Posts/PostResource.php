<?php

namespace App\Filament\Resources\Posts;

use App\Models\Post;
use App\Rules\DuplicateRule;
use App\Rules\UniqueColorSizeCombination;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Model;

use function Laravel\Prompts\select;

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

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')->label('نام محصول')->required(),
            Textarea::make('content')->label('توضیحات')->required(),
            FileUpload::make('picture')->directory('posts')->image()->label('عکس محصول')->required(),
            TextInput::make('default_price')->label('قیمت')->numeric()->prefix('تومان')->required(),
            Select::make('category')->label('دسته بندی')->options([ 'men' => 'مردانه','women' => 'زنانه','shoes' => 'کفش','accessories' => 'اکسسوری',])->required(),
              Checkbox::make('has_discount')
            ->label('آیا محصول تخفیف دارد؟')->default(false)
            ->reactive(),


         Section::make('ساخت خودکار تنوع‌ها')
    ->description('اینجا می‌توانید رنگ، سایز، قیمت و موجودی را تعیین کنید.')
    ->schema([
        Select::make('temp_color')
            ->label('رنگ')
            ->options([
                'red' => 'قرمز',
                'blue' => 'آبی',
                'green' => 'سبز',
                'black' => 'مشکی',
                'white' => 'سفید',
            ]),

        CheckboxList::make('temp_sizes')
            ->label('سایزها')
            ->options([
                'S'  => 'S',
                'M'  => 'M',
                'L'  => 'L',
                'XL' => 'XL',
                '2XL'=> '2XL',
            ])
            ->columns(3),

        TextInput::make('temp_price')
            ->label('قیمت برای همه')
            ->numeric(),

        TextInput::make('temp_stock')
            ->label('موجودی برای همه')
            ->numeric(),

        Actions::make([
        Action::make('generate')
            ->label('ساخت کارت‌ها')
            ->action(function (callable $get, callable $set) {
                $color = $get('temp_color');
                $sizes = $get('temp_sizes');
                $price = $get('temp_price');
                $stock = $get('temp_stock');

                $variants = [];


                foreach ($sizes as $size) {
                    $variants[] = [
                        'color' => $color,
                        'size' => $size,
                        'price' => $price,
                        'stock' => $stock,
                    ];
                }

                // push to real repeater
                $set('variants', array_merge($get('variants') ?? [], $variants));
                $set('temp_color', null);
                $set('temp_sizes', []);
                $set('temp_price', null);
                $set('temp_stock', null);
            })
    ])



    ])
    ->collapsible() // اختیاری: اگر می‌خواهید بخش قابل جمع شدن باشد
    ->extraAttributes([
        'class' => 'bg-white border border-gray-300 rounded-lg p-4 shadow-md', // استایل سفارشی
    ])
    ->columns(),




   Repeater::make('variants')
    ->relationship('variants')
    ->label('ترکیب رنگ و سایز')
    ->schema([

        Select::make('color')
            ->label('رنگ')
            ->options([
                'red' => 'قرمز',
                'blue' => 'آبی',
                'green' => 'سبز',
                'black' => 'مشکی',
                'white' => 'سفید',
            ])
            ->required()
            ->reactive(),

        Select::make('size')
            ->label('سایز')
            ->options([
                'S'  => 'S',
                'M'  => 'M',
                'L'  => 'L',
                'XL' => 'XL',
                '2XL'=> '2XL',
            ])
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
            ]),



        Group::make([
            TextInput::make('discount_percent')
                ->label('درصد تخفیف')
                ->numeric()
                ->minValue(1)
                ->maxValue(100),

            DatePicker::make('discount_start')
                ->label('تاریخ شروع تخفیف'),

            DatePicker::make('discount_end')
                ->label('تاریخ پایان تخفیف'),

            Textarea::make('discount_note')
                ->label('توضیحات تخفیف')
                ->rows(2),
        ])
        ->visible(fn (callable $get) => $get('has_discount')),

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

            // ستون تخفیف‌ها در یک گروه منطقی
            TextColumn::make('discount_percent')
                ->label('درصد تخفیف')
                ->suffix('%')
                ->visible()
                ->sortable(),

            TextColumn::make('discount_start')
                ->label('شروع تخفیف')
                ->date()
                ->visible(),

            TextColumn::make('discount_end')
                ->label('پایان تخفیف')
                ->date()
                ->visible(),

            TextColumn::make('created_at')
                ->label('تاریخ ایجاد')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            // فیلتر برای محصولات دارای تخفیف
            Filter::make('has_discount')
                ->label('فقط تخفیف‌دارها')
                ->query(fn ($query) => $query->where('has_discount', true)),
        ])
        ->defaultSort('id', 'desc');
}

}
