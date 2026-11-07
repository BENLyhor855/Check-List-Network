import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting, HttpTestingController } from '@angular/common/http/testing';
import { Workspace } from './workspace';
describe('Workspace navigation', () => {
  it('cancels stale requests when switching resource', () => {
    TestBed.configureTestingModule({
      imports: [Workspace],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    const fixture = TestBed.createComponent(Workspace);
    fixture.componentRef.setInput('user', {
      id: 1,
      email: 'test@example.test',
      role: 'Super Administrator',
    });
    fixture.detectChanges();
    const http = TestBed.inject(HttpTestingController);
    const old = http.expectOne('/api/v1/devices?offset=0');
    fixture.componentInstance.navigate('buildings');
    expect(old.cancelled).toBe(true);
    http
      .expectOne('/api/v1/buildings?offset=0')
      .flush({ items: [{ id: 1, name: 'Building' }], limit: 100, offset: 0 });
    expect(fixture.componentInstance.rows()[0]['name']).toBe('Building');
    fixture.destroy();
    http.verify();
  });
});
