import { Component, computed, input, output } from '@angular/core';
import { PAGE_SIZE, RecordRow, Resource } from '../../core/api/models';
@Component({
  selector: 'app-record-table',
  templateUrl: './record-table.html',
  styleUrl: './record-table.css',
})
export class RecordTable {
  readonly rows = input.required<RecordRow[]>();
  readonly resource = input.required<Resource>();
  readonly busy = input(false);
  readonly offset = input(0);
  readonly refreshed = output<void>();
  readonly pageChanged = output<number>();
  readonly pageSize = PAGE_SIZE;
  readonly columns = computed(() => Object.keys(this.rows()[0] ?? {}));
}
