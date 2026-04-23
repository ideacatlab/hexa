<?php

namespace Hexters\HexaLite\Resources\IAM\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IAMRolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('Role Name')),
                TextColumn::make('guard')
                    ->badge()
                    ->sortable()
                    ->label(__('Guard')),
                TextColumn::make('parent.name')
                    ->placeholder('—')
                    ->sortable()
                    ->label(__('Parent Role')),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->sortable()
                    ->label(__('Users')),
                TextColumn::make('created_by_name')
                    ->searchable()
                    ->label(__('Created By')),
                TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime('d/m/y H:i'),
            ])
            ->filters([
                SelectFilter::make('guard')
                    ->label(__('Guard'))
                    ->options(function () {
                        $guards = \Hexters\HexaLite\Models\HexaRole::query()
                            ->distinct()
                            ->pluck('guard')
                            ->filter()
                            ->mapWithKeys(fn ($guard) => [$guard => ucfirst($guard)]);

                        return $guards->toArray();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->button()
                    ->visible(fn () => hexa()->can('iam.update')),
                DeleteAction::make()
                    ->button()
                    ->visible(fn () => hexa()->can('iam.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => hexa()->can('iam.delete')),
                ]),
            ]);
    }
}
