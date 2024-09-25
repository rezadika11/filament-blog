<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Filament\Support\Colors\Color;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make([
                    'default' => 1,
                    'sm' => 3,
                ])
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->placeholder('Input Title')
                                            ->maxLength(255)
                                            ->rules('required')
                                            ->reactive()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                                $set('slug', Str::slug($state));
                                            })->debounce(300),

                                        Forms\Components\TextInput::make('slug')
                                            ->placeholder('Slug')
                                            ->readOnly()
                                            ->maxLength(255)
                                            ->rules('required')
                                            ->reactive()
                                            ->dehydrated()
                                    ]),
                                Forms\Components\Textarea::make('excerpt')
                                    ->placeholder('Excerpt')
                                    ->rules('required')
                                    ->rows(5),
                                Forms\Components\MarkdownEditor::make('body')
                                    ->label('Content')
                                    ->rules('required'),


                                Forms\Components\FileUpload::make('image')
                                    ->label('Image')
                                    ->image(),

                                Forms\Components\Textarea::make('meta_description')
                                    ->label('Meta Description')
                                    ->placeholder('Meta Description')
                                    ->rules('required')
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 2,
                            ]),

                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->options(\App\Models\Category::all()->pluck('name', 'id'))
                                    ->rules('required')
                                    ->searchable(),

                                Forms\Components\Select::make('tags')
                                    ->label('Tags')
                                    ->multiple()
                                    ->relationship('tags', 'name')
                                    ->preload()
                                    ->options(\App\Models\Tag::all()->pluck('name', 'id'))
                                    ->rules('required')
                                    ->searchable(),

                                Forms\Components\Select::make('user_id')
                                    ->label('Posted By')
                                    ->options(\App\Models\User::all()->pluck('name', 'id'))
                                    ->rules('required')
                                    ->searchable(),

                                Forms\Components\DatePicker::make('published')
                                    ->label('Published at')
                                    ->rules('required'),

                                Forms\Components\Toggle::make('status')
                                    ->label('Published')
                                    ->onColor('success')
                                    ->offColor('gray')
                                    ->onIcon('heroicon-m-check')
                                    ->offIcon('heroicon-m-x-mark')
                                    ->inline()
                                    ->required()
                                    ->rules('required')
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')->sortable(),
                Tables\Columns\TextColumn::make('published')->date(),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Published')
                    ->onColor(Color::Green)
                    ->onIcon('heroicon-m-check')
                    ->offIcon('heroicon-m-x-mark')
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Post $record) {
                        if ($record->image && Storage::disk('public')->exists($record->image)) {
                            Storage::disk('public')->delete($record->image);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function (Collection $records) {
                        foreach ($records as $record) {
                            if ($record->image && Storage::disk('public')->exists($record->image)) {
                                Storage::disk('public')->delete($record->image);
                            }
                        }
                    }),
            ]);
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
