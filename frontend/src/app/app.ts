import { Component, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AuthService } from './core/auth/auth-service';
import { Login } from './features/auth/login';
import { Workspace } from './features/inventory/workspace';
@Component({ selector: 'app-root', imports: [Login, Workspace], templateUrl: './app.html' })
export class App {
  readonly auth = inject(AuthService);
  readonly checkingSession = signal(true);
  private readonly destroyRef = inject(DestroyRef);
  constructor() {
    this.auth
      .restore()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.checkingSession.set(false),
        error: () => this.checkingSession.set(false),
      });
  }
}
