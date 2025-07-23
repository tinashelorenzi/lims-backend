<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $needsSetup = User::where('account_is_set', false)->count();
        $adminUsers = User::where('user_type', 'admin')->count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Active Users', $activeUsers)
                ->description('Users with active accounts')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Needs Setup', $needsSetup)
                ->description('Users requiring account setup')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Administrators', $adminUsers)
                ->description('Users with admin privileges')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),
        ];
    }
}