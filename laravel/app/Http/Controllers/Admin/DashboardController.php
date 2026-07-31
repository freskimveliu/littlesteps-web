<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\ChildReward;
use App\Models\TemplateAchievement;
use App\Models\TemplateLevel;
use App\Models\TemplateMilestone;
use App\Models\TemplatePrompt;
use App\Models\TemplateStep;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'catalogue' => [
                ['label' => 'Chapters', 'value' => TemplateMilestone::count(), 'href' => '/admin/chapters'],
                ['label' => 'Steps', 'value' => TemplateStep::count(), 'href' => '/admin/steps'],
                ['label' => 'Categories', 'value' => Category::count(), 'href' => '/admin/categories'],
                ['label' => 'Badges', 'value' => TemplateAchievement::count(), 'href' => '/admin/badges'],
                ['label' => 'Levels', 'value' => TemplateLevel::count(), 'href' => '/admin/levels'],
                ['label' => 'Prompts', 'value' => TemplatePrompt::count(), 'href' => '/admin/prompts'],
            ],
            'usage' => [
                ['label' => 'Parents', 'value' => User::count(), 'href' => '/admin/users'],
                ['label' => 'Children', 'value' => Child::count(), 'href' => '/admin/children'],
                ['label' => 'Memories', 'value' => ChildEntry::count(), 'href' => null],
                ['label' => 'Gifts earned', 'value' => ChildReward::count(), 'href' => '/admin/gifts'],
            ],
            'gifts' => TemplateAchievement::whereNotNull('reward')->orderBy('sort_order')->get([
                'id', 'name', 'reward', 'metric', 'threshold',
            ]),
        ]);
    }
}
