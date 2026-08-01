<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The only things a trophy rule can count.
 *
 * Every one of these is gated by the calendar rather than by how fast a parent
 * can type: the catalogue holds a finite number of milestones, so a raw entry count
 * would let a determined parent clear the whole ladder in a fortnight.
 */
enum TrophyMetric: string
{
    case Days = 'days';
    case Months = 'months';
    case Streak = 'streak';
    case OnTimeMilestones = 'on_time_milestones';
    case Chapters = 'chapters';
    case Photos = 'photos';
    case Categories = 'categories';
}
