import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CreateRecord, RecordPage, RecordRow, Resource, WritableResource } from './models';
@Injectable({ providedIn: 'root' })
export class InventoryApi {
  private readonly http = inject(HttpClient);
  list<T extends RecordRow = RecordRow>(resource: Resource, offset = 0) {
    return this.http.get<RecordPage<T>>(`/api/v1/${resource}`, { params: { offset } });
  }
  create(resource: WritableResource, input: CreateRecord) {
    return this.http.post<{ id: number }>(`/api/v1/${resource}`, input);
  }
}
