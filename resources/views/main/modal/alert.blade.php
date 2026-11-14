{{--
    Flash alerts (App\Helpers\Alert) rendered with SweetAlert; @json keeps the message safely encoded.
    Success and info messages appear as a non-blocking toast, warnings and errors as a dialog.
--}}
@if (session()->has('alert'))
    <script>
        (function() {
            const alert = @json(session('alert'));
            const isToast = ['success', 'info'].includes(alert.icon);

            Swal.fire(Object.assign(isToast ? {
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            } : {
                confirmButtonText: 'OK',
            }, alert));
        })();
    </script>
@endif
