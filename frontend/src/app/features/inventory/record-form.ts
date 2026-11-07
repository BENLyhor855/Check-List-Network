import { Component, DestroyRef, inject, input, OnInit, output, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule, NgForm } from '@angular/forms';
import { InventoryApi } from '../../core/api/inventory-api';
import { Building, CreateRecord, Location, WritableResource } from '../../core/api/models';
import { errorMessage } from '../../core/api/error-message';
@Component({
  selector: 'app-record-form',
  imports: [FormsModule],
  templateUrl: './record-form.html',
  styleUrl: './record-form.css',
})
export class RecordForm implements OnInit {
  readonly resource = input.required<WritableResource>();
  readonly saved = output<void>();
  readonly cancelled = output<void>();
  private readonly api = inject(InventoryApi);
  private readonly destroyRef = inject(DestroyRef);
  readonly buildings = signal<Building[]>([]);
  readonly locations = signal<Location[]>([]);
  readonly error = signal('');
  readonly busy = signal(false);
  name = '';
  assetId = '';
  manufacturer = '';
  model = '';
  managementIp = '';
  category = 'switch';
  locationId: number | null = null;
  buildingId: number | null = null;
  ngOnInit() {
    if (this.resource() === 'locations')
      this.api
        .list<Building>('buildings')
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (r) => this.buildings.set(r.items),
          error: (e) => this.error.set(errorMessage(e)),
        });
    if (this.resource() === 'devices')
      this.api
        .list<Location>('locations')
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (r) => this.locations.set(r.items),
          error: (e) => this.error.set(errorMessage(e)),
        });
  }
  save(form: NgForm) {
    if (form.invalid || this.busy()) return;
    const resource = this.resource();
    let body: CreateRecord = { name: this.name };
    if (resource === 'locations') {
      if (this.buildingId === null) return;
      body.building_id = this.buildingId;
    }
    if (resource === 'devices') {
      if (this.locationId === null) return;
      body = {
        ...body,
        asset_id: this.assetId,
        manufacturer: this.manufacturer,
        model: this.model,
        management_ip: this.managementIp,
        category: this.category,
        location_id: this.locationId,
        notes: '',
      };
    }
    this.error.set('');
    this.busy.set(true);
    this.api
      .create(resource, body)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.busy.set(false);
          this.saved.emit();
        },
        error: (e) => {
          this.busy.set(false);
          this.error.set(errorMessage(e));
        },
      });
  }
}
