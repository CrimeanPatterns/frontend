interface MiniMap {
    points: string[];
    arrTime: string | boolean;
}

interface Origins {
    auto?: (AccountOrigin | ConfNumberOrigin | EmailOrigin)[];
    manual?: boolean;
}

interface AccountOrigin {
    accountId: number;
    provider: string;
    accountNumber: string;
    owner: string;
}
interface ConfNumberOrigin {
    provider: string;
    confNumber: string;
}
interface EmailOrigin {
    from: 0 | 1 | 2; // 0 - unknown, 1 - plans, 2 - scanner
    email: string;
}

interface Details {
    accountId?: number;
    agentId?: number;
    refreshLink?: string;
    autoLoginLink?: string;
    bookingLink?: {
        info: string;
        url: string;
        formFields: {
            destination: string;
            checkinDate: string;
            checkoutDate: string;
            url: string;
        };
    };
    canEdit?: boolean;
    canCheck?: boolean;
    canAutoLogin?: boolean;
    Status?: string;
    shareCode?: string;
    monitoredStatus?: string;
    Fax?: string;
    GuestCount?: number;
    KidsCount?: number;
    Rooms?: number;
    RoomLongDescriptions?: string;
    RoomShortDescriptions?: string;
    RoomRate?: string;
    RoomRateDescription?: string;
    TravelerNames?: string;
    CancellationPolicy?: string;
    CarDescription?: string;
    LicensePlate?: string;
    SpotNumber?: string;
    CarModel?: string;
    CarType?: string;
    PickUpFax?: string;
    DropOffFax?: string;
    DinerName?: string;
    CruiseName?: string;
    Deck?: string;
    CabinNumber?: string;
    ShipCode?: string;
    ShipName?: string;
    ShipCabinClass?: string;
    Smoking?: string;
    Stops?: number;
    ServiceClasses?: string;
    ServiceName?: string;
    CarNumber?: string;
    AdultsCount?: number;
    Aircraft?: string;
    TicketNumbers?: string;
    TravelledMiles?: string;
    Meal?: string;
    BookingClass?: string;
    CabinClass?: string;
    phone?: string;
    columns?: Column[];
}

type Column =
    | { type: 'arrow' }
    | {
          type: 'info';
          rows: (
              | { type: 'arrow' }
              | { type: 'checkin'; date: string; nights: number }
              | {
                    type: 'datetime';
                    date: string;
                    time: string;
                    prevTime?: string;
                    prevDate?: string;
                    timestamp?: number;
                    timezone?: string;
                    formattedDate?: string;
                    arrivalDay?: string;
                }
              | { type: 'text'; text: string; geo: { country?: string; state?: string; city?: string } }
              | { type: 'pairs'; pairs: { [index: string]: unknown } }
              | { type: 'pair'; name: 'Guests'; value: number }
              | { type: 'parkingStart'; date: string; days: number }
              | { type: 'pickup'; date: string; days: number }
              | { type: 'pickup.taxi'; date: string; time: string }
              | { type: 'dropoff'; date: string; time: string }
              | { type: 'airport'; text: { place: string; code: string } }
          )[];
      };

export interface TimelineSegment {
    id: number;
    startDate: number;
    endDate: number;
    startTimezone: string;
    breakAfter: boolean;

    icon: string;
    map: MiniMap;
    localTime: string;
    localDate: number;
    localDateISO: string;
    localDateTimeISO: string;
    origins?: Origins;
    confNo?: string;
    group?: string;
    changed?: boolean;
    deleted?: boolean;
    lastSync?: number;
    lastUpdated?: number;
    title?: string;
    prevTime?: string;
    segments?: number;
    details?: Details;
}

export interface PlanFile {
    id: number,
    fileName: string,
    description?: string,
    fileSize: number,
    uploadDate: string,
}

export type SegmentType = {
    segment: TimelineSegmentType,
    opened: boolean,
    form?: any,
};

export type TimelineSegmentType = {
    id: number;
    startDate: number;
    endDate: number;
    startTimezone: string;
    breakAfter: boolean;

    icon: string;
    map: MiniMap;
    localTime: string;
    localDate: number;
    localDateISO: string;
    localDateTimeISO: string;
    origins?: Origins;
    confNo?: string;
    group?: string;
    changed?: boolean;
    deleted?: boolean;
    lastSync?: number;
    lastUpdated?: number;
    title?: string;
    prevTime?: string;
    segments?: number;
    details?: Details;
    notes?: NotesType;
    preset?: string,
}

export type NotesType = {
    text: string,
    files: PlanFile[],
}
