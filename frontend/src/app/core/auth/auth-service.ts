import { inject, Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { switchMap, tap } from 'rxjs';
import { User } from '../api/models';
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly currentUser = signal<User | null>(null);
  readonly user = this.currentUser.asReadonly();
  restore() {
    return this.http.get<User>('/api/v1/auth/me').pipe(tap((user) => this.currentUser.set(user)));
  }
  login(email: string, password: string) {
    return this.http
      .post('/api/v1/auth/login', { email, password })
      .pipe(switchMap(() => this.restore()));
  }
  logout() {
    return this.http
      .post<void>('/api/v1/auth/logout', {})
      .pipe(tap(() => this.currentUser.set(null)));
  }
}
