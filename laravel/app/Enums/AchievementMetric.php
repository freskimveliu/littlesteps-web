<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The only things a badge rule can count.
 *
 * Every one of these is gated by the calendar rather than by how fast a parent
 * can type: the catalogue holds a finite number of steps, so a raw entry count
 * would let a determined parent clear the whole ladder in a fortnight.
 */
enum AchievementMetric: string
{
    case Days = 'days';
    case Months = 'months';
    case Streak = 'streak';
    case OnTimeSteps = 'on_time_steps';
    case Milestones = 'milestones';
    case Photos = 'photos';
    case Categories = 'categories';
}
