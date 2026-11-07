import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Subscription } from 'rxjs';
import { AuthService } from '../../core/auth/auth-service';
import { InventoryApi } from '../../core/api/inventory-api';
import { RecordRow, Resource, RESOURCES, resourceTitle, User } from '../../core/api/models';
import { errorMessage } from '../../core/api/error-message';
import { RecordForm } from './record-form';
import { RecordTable } from '../../shared/record-table/record-table';
@Component({
  selector: 'app-workspace',
  imports: [RecordForm, RecordTable],
  templateUrl: './workspace.html',
  styleUrl: './workspace.css',
})
export class Workspace implements OnInit {
  readonly user = input.required<User>();
  private readonly api = inject(InventoryApi);
  private readonly auth = inject(AuthService);
  private readonly destroyRef = inject(DestroyRef);
  private listRequest?: Subscription;
  readonly resources = RESOURCES;
  readonly resourceTitle = resourceTitle;
  readonly section = signal<Resource>('devices');
  readonly offset = signal(0);
  readonly rows = signal<RecordRow[]>([]);
  readonly error = signal('');
  readonly busy = signal(false);
  readonly showForm = signal(false);
  readonly writableResource = computed(() => {
    const section = this.section();
    return section === 'audit' ? null : section;
  });
  ngOnInit() {
    this.load();
  }
  navigate(resource: Resource) {
    this.section.set(resource);
    this.offset.set(0);
    this.showForm.set(false);
    this.rows.set([]);
    this.load();
  }
  load() {
    // Cancel previous navigation requests so an old response cannot overwrite the current page.
    this.listRequest?.unsubscribe();
    this.busy.set(true);
    this.error.set('');
    this.listRequest = this.api
      .list(this.section(), this.offset())
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (r) => {
          this.rows.set(r.items);
          this.busy.set(false);
        },
        error: (e) => {
          this.busy.set(false);
          this.error.set(errorMessage(e));
        },
      });
  }
  page(delta: number) {
    this.offset.update((v) => Math.max(0, v + delta));
    this.load();
  }
  openForm() {
    this.showForm.set(true);
  }
  recordSaved() {
    this.showForm.set(false);
    this.load();
  }
  logout() {
    this.auth
      .logout()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({ error: (e) => this.error.set(errorMessage(e)) });
  }
}
