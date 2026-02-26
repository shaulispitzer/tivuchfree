declare namespace App.Data {
export type LocaleData = {
locale: App.Enums.Locale;
};
export type PropertyData = {
id: number;
user_id: number | null;
contact_name: string | null;
contact_phone: string | null;
neighbourhoods: Array<any>;
price: number | null;
street: string;
lat: number | null;
lon: number | null;
building_number: number;
floor: number;
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
user: App.Data.UserData | null;
main_image_url: string | null;
image_urls: Array<any>;
created_at: string;
};
export type PropertyFormOptionsData = {
neighbourhoods: Array<any>;
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
id: number;
name: string;
email: string;
is_admin: boolean;
created_at: string;
google_avatar: string | null;
};
}
declare namespace App.Data.Forms {
export type PropertyFormData = {
contact_name: string;
contact_phone: string;
email: string | null;
price: number | null;
neighbourhoods: Array<any>;
building_number: number | null;
street: number;
floor: number;
square_meter: number | null;
type: App.Enums.PropertyLeaseType;
available_from: string;
available_to: string | null;
bedrooms: number;
bathrooms: number | null;
furnished: App.Enums.PropertyFurnished;
access: App.Enums.PropertyAccess;
kitchen_dining_room: App.Enums.PropertyKitchenDiningRoom;
porch_garden: App.Enums.PropertyPorchGarden;
succah_porch: boolean;
air_conditioning: App.Enums.PropertyAirConditioning;
apartment_condition: App.Enums.PropertyApartmentCondition;
additional_info: string | null;
additional_info_en: string | null;
additional_info_he: string | null;
has_dud_shemesh: boolean;
has_machsan: boolean;
has_parking_spot: boolean;
temp_upload_id: number | null;
image_media_ids: Array<any> | null;
main_image_media_id: number | null;
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
export type PropertyOptionLabel = {
};
export type Locale = 'en' | 'he';
export type Neighbourhood = 'Sanhedria' | 'Sanhedria Murchavet' | 'Bar Ilan' | 'Gush 80' | 'Belz' | 'Romema' | 'Sorotzkin' | 'Mekor Baruch' | 'Geula';
export type NotificationType = 'success' | 'error' | 'warning' | 'info' | 'default';
export type PropertyAccess = 'step_free_access' | 'steps_only' | 'elevator_non_shabbos' | 'elevator_shabbos';
export type PropertyAirConditioning = 'fully_airconditioned' | 'partly_airconditioned' | 'not_airconditioned';
export type PropertyApartmentCondition = 'brand_new' | 'excellent' | 'good' | 'lived_in';
export type PropertyFurnished = 'fully_furnished' | 'partially_furnished' | 'not_furnished';
export type PropertyKitchenDiningRoom = 'separate' | 'not_separate' | 'partly_separate' | 'no_kitchen';
export type PropertyLeaseType = 'medium_term' | 'long_term';
export type PropertyPorchGarden = 'porch' | 'garden' | 'no';
}
