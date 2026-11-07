import { HttpErrorResponse } from '@angular/common/http';
export function errorMessage(error: unknown): string {
  if (error instanceof HttpErrorResponse && typeof error.error?.error === 'string')
    return error.error.error;
  return 'Service unavailable. Please retry.';
}
