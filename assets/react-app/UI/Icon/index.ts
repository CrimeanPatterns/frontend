export * from './Icon';
import ArrowDown from './Assets/arrow-down.svg';
import ArrowLeft from './Assets/arrow-left.svg';
import ArrowRight from './Assets/arrow-right.svg';
import ArrowRightWithPoints from './Assets/arrow-right-points.svg';
import Brand from './Assets/brand.svg';
import Calendar from './Assets/calendar.svg';
import Change from './Assets/change.svg';
import CheckedCalendar from './Assets/checked-calendar.svg';
import ChevronDown from './Assets/chevron-down.svg';
import ChevronUp from './Assets/chevron-up.svg';
import Clock from './Assets/clock.svg';
import Copy from './Assets/copy.svg';
import DoubleTick from './Assets/double-tick.svg';
import Download from './Assets/download.svg';
import Expand from './Assets/expand.svg';
import Filter from './Assets/filter.svg';
import FilterActive from './Assets/filter-active.svg';
import Location from './Assets/location.svg';
import Minus from './Assets/minus.svg';
import MuteVolume from './Assets/mute-volume.svg';
import Next from './Assets/next.svg';
import NoWarranty from './Assets/no-warranty.svg';
import NonTransferable from './Assets/non-transferable.svg';
import Pause from './Assets/pause.svg';
import Person from './Assets/person.svg';
import Play from './Assets/play.svg';
import Plus from './Assets/plus.svg';
import Previous from './Assets/previous.svg';
import Refund from './Assets/refund.svg';
import Search from './Assets/search.svg';
import Settings from './Assets/settings.svg';
import Share from './Assets/share.svg';
import Sorting from './Assets/sorting.svg';
import Star from './Assets/star.svg';
import Trash from './Assets/trash.svg';
import Update from './Assets/update.svg';
import Volume from './Assets/volume.svg';
import Warning from './Assets/warning.svg';

//Size options have to according to size classes in Icon.module.scss
export type IconSize = 'big' | 'medium' | 'small';
export type IconColor = 'secondary' | 'active' | 'primary' | 'disabled' | 'warning';

export const Icons = {
    Calendar,
    Search,
    Location,
    ArrowRight,
    ArrowLeft,
    Plus,
    Minus,
    Person,
    ChevronDown,
    ChevronUp,
    Filter,
    Sorting,
    Star,
    DoubleTick,
    Change,
    ArrowRightWithPoints,
    CheckedCalendar,
    Trash,
    Copy,
    Clock,
    Warning,
    Expand,
    ArrowDown,
    FilterActive,
    Brand,
    Volume,
    MuteVolume,
    Play,
    Pause,
    NonTransferable,
    Settings,
    NoWarranty,
    Refund,
    Previous,
    Next,
    Download,
    Share,
    Update,
};

export type IconType = keyof typeof Icons;
