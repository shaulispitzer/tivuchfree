declare namespace App.Data {
export type LocaleData = {
locale: App.Enums.Locale;
};
export type PropertyData = {
id: number;
user_id: number;
neighbourhood_id: number | null;
listing_id: string;
price: number | null;
street: string;
building_number: string | null;
floor: string;
type: App.Enums.PropertyLeaseType;
available_from: string;
available_to: string | null;
bedrooms: number;
square_meter: number | null;
views: number;
furnished: App.Enums.PropertyFurnished;
taken: boolean;
bathrooms: number | null;
access: App.Enums.PropertyAccess | null;
kitchen_dining_room: App.Enums.PropertyKitchenDiningRoom | null;
porch_garden: App.Enums.PropertyPorchGarden | null;
succah_porch: boolean;
air_conditioning: App.Enums.PropertyAirConditioning | null;
apartment_condition: App.Enums.PropertyApartmentCondition | null;
additional_info: string | null;
has_dud_shemesh: boolean;
has_machsan: boolean;
has_parking_spot: boolean;
user: App.Data.UserData;
main_image_url: string | null;
image_urls: Array<any>;
can_edit: boolean;
};
export type PropertyFormOptionsData = {
lease_types: Array<any>;
furnished: Array<any>;
access: Array<any>;
kitchen_dining_room: Array<any>;
porch_garden: Array<any>;
air_conditioning: Array<any>;
apartment_condition: Array<any>;
};
export type PropertyOptionData = {
value: string;
label: string;
};
export type UserData = {
id: number | null;
name: string;
email: string;
is_admin: boolean | null;
created_at: string | null;
};
}
declare namespace App.Data.Shared {
export type NotificationData = {
type: App.Enums.NotificationType;
body: string;
};
export type SharedData = {
user: App.Data.UserData;
notification: App.Data.Shared.NotificationData | null;
locale: string | null;
};
}
declare namespace App.Enums {
export type Locale = 'en' | 'he';
export type PropertyOptionLabel = {
};
export type NotificationType = 'success' | 'error' | 'warning' | 'info' | 'default';
export type PropertyAccess = 'step_free_access' | 'steps_only' | 'elevator_non_shabbos' | 'elevator_shabbos';
export type PropertyAirConditioning = 'fully_airconditioned' | 'partly_airconditioned' | 'not_airconditioned';
export type PropertyApartmentCondition = 'brand_new' | 'excellent' | 'good' | 'lived_in';
export type PropertyFurnished = 'yes' | 'partially' | 'no';
export type PropertyKitchenDiningRoom = 'separate' | 'not_separate' | 'partly_separate' | 'no_kitchen';
export type PropertyLeaseType = 'medium_term' | 'long_term';
export type PropertyPorchGarden = 'porch' | 'garden' | 'no';
}
