import { Component, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule, NgForm } from '@angular/forms';
import { AuthService } from '../../core/auth/auth-service';
import { errorMessage } from '../../core/api/error-message';
@Component({
  selector: 'app-login',
  imports: [FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  private readonly auth = inject(AuthService);
  private readonly destroyRef = inject(DestroyRef);
  readonly error = signal('');
  readonly busy = signal(false);
  email = '';
  password = '';
  login(form: NgForm) {
    if (form.invalid || this.busy()) return;
    this.error.set('');
    this.busy.set(true);
    this.auth
      .login(this.email, this.password)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.password = '';
          this.busy.set(false);
        },
        error: (error) => {
          this.busy.set(false);
          this.error.set(errorMessage(error));
        },
      });
  }
}
