export interface User {
  id: number;
  email: string;
  role: string;
}
export type WritableResource = 'devices' | 'buildings' | 'locations';
export type Resource = WritableResource | 'audit';
export interface RecordRow {
  id: number;
  [key: string]: string | number;
}
export interface Building extends RecordRow {
  name: string;
}
export interface Location extends RecordRow {
  name: string;
  building_id: number;
}
export interface RecordPage<T = RecordRow> {
  items: T[];
  limit: number;
  offset: number;
}
export interface CreateRecord {
  name: string;
  building_id?: number;
  location_id?: number;
  asset_id?: string;
  manufacturer?: string;
  model?: string;
  management_ip?: string;
  category?: string;
  notes?: string;
}
export const PAGE_SIZE = 100;
export const RESOURCES: readonly Resource[] = ['devices', 'buildings', 'locations', 'audit'];
export function resourceTitle(resource: Resource): string {
  return resource === 'devices'
    ? 'Network inventory'
    : resource === 'audit'
      ? 'Audit trail'
      : resource;
}
