<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Every Ionicons name the catalogue is allowed to use.
 *
 * The app renders these straight through, so a name that is not a real
 * Ionicons glyph draws an empty square rather than failing loudly — which
 * is why the admin picks from this list instead of typing one.
 */
enum Icon: string
{
    case AirplaneOutline = 'airplane-outline';
    case Albums = 'albums';
    case AlertCircleOutline = 'alert-circle-outline';
    case ArrowUpOutline = 'arrow-up-outline';
    case BalloonOutline = 'balloon-outline';
    case Barbell = 'barbell';
    case BaseballOutline = 'baseball-outline';
    case BedOutline = 'bed-outline';
    case BicycleOutline = 'bicycle-outline';
    case BodyOutline = 'body-outline';
    case Book = 'book';
    case BookOutline = 'book-outline';
    case Bookmarks = 'bookmarks';
    case BrushOutline = 'brush-outline';
    case Bulb = 'bulb';
    case Calendar = 'calendar';
    case CalendarOutline = 'calendar-outline';
    case Camera = 'camera';
    case CameraOutline = 'camera-outline';
    case CarOutline = 'car-outline';
    case ChatbubbleOutline = 'chatbubble-outline';
    case Chatbubbles = 'chatbubbles';
    case ChatbubblesOutline = 'chatbubbles-outline';
    case Cloud = 'cloud';
    case CloudOutline = 'cloud-outline';
    case ColorPalette = 'color-palette';
    case ColorPaletteOutline = 'color-palette-outline';
    case Compass = 'compass';
    case ConstructOutline = 'construct-outline';
    case CubeOutline = 'cube-outline';
    case CutOutline = 'cut-outline';
    case Diamond = 'diamond';
    case EarOutline = 'ear-outline';
    case Earth = 'earth';
    case EyeOutline = 'eye-outline';
    case Film = 'film';
    case Flame = 'flame';
    case Flash = 'flash';
    case FlashOutline = 'flash-outline';
    case FlowerOutline = 'flower-outline';
    case FolderOpen = 'folder-open';
    case FootstepsOutline = 'footsteps-outline';
    case GameControllerOutline = 'game-controller-outline';
    case Gift = 'gift';
    case GiftOutline = 'gift-outline';
    case HandLeftOutline = 'hand-left-outline';
    case HandRightOutline = 'hand-right-outline';
    case Happy = 'happy';
    case HappyOutline = 'happy-outline';
    case Heart = 'heart';
    case HeartCircleOutline = 'heart-circle-outline';
    case HeartOutline = 'heart-outline';
    case HelpOutline = 'help-outline';
    case HomeOutline = 'home-outline';
    case Hourglass = 'hourglass';
    case Images = 'images';
    case Infinite = 'infinite';
    case Leaf = 'leaf';
    case LeafOutline = 'leaf-outline';
    case Library = 'library';
    case MailOutline = 'mail-outline';
    case Map = 'map';
    case Medal = 'medal';
    case Medkit = 'medkit';
    case MedkitOutline = 'medkit-outline';
    case MoonOutline = 'moon-outline';
    case MusicalNoteOutline = 'musical-note-outline';
    case MusicalNotesOutline = 'musical-notes-outline';
    case NutritionOutline = 'nutrition-outline';
    case PawOutline = 'paw-outline';
    case PeopleOutline = 'people-outline';
    case PersonOutline = 'person-outline';
    case PizzaOutline = 'pizza-outline';
    case RefreshOutline = 'refresh-outline';
    case RestaurantOutline = 'restaurant-outline';
    case Ribbon = 'ribbon';
    case Rocket = 'rocket';
    case RocketOutline = 'rocket-outline';
    case SchoolOutline = 'school-outline';
    case Search = 'search';
    case ShieldCheckmark = 'shield-checkmark';
    case ShieldCheckmarkOutline = 'shield-checkmark-outline';
    case ShieldOutline = 'shield-outline';
    case ShirtOutline = 'shirt-outline';
    case Sparkles = 'sparkles';
    case SparklesOutline = 'sparkles-outline';
    case SpeedometerOutline = 'speedometer-outline';
    case Star = 'star';
    case StarOutline = 'star-outline';
    case Sunny = 'sunny';
    case SunnyOutline = 'sunny-outline';
    case TextOutline = 'text-outline';
    case ThermometerOutline = 'thermometer-outline';
    case Time = 'time';
    case TimeOutline = 'time-outline';
    case Today = 'today';
    case TrendingUp = 'trending-up';
    case TrendingUpOutline = 'trending-up-outline';
    case Trophy = 'trophy';
    case TrophyOutline = 'trophy-outline';
    case TvOutline = 'tv-outline';
    case VolumeHighOutline = 'volume-high-outline';
    case Walk = 'walk';
    case WalkOutline = 'walk-outline';
    case WaterOutline = 'water-outline';
}
