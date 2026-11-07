import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting, HttpTestingController } from '@angular/common/http/testing';
import { App } from './app';
describe('App',()=>{
 it('shows sign-in after an unauthenticated session check',async()=>{
 await TestBed.configureTestingModule({imports:[App],providers:[provideHttpClient(),provideHttpClientTesting()]}).compileComponents();
 const fixture=TestBed.createComponent(App);const http=TestBed.inject(HttpTestingController);
 http.expectOne('/api/v1/auth/me').flush({error:'authentication required'},{status:401,statusText:'Unauthorized'});
 fixture.detectChanges();expect(fixture.nativeElement.textContent).toContain('Sign in');expect(fixture.nativeElement.textContent).not.toContain('Registered assets');http.verify();
 });
});
