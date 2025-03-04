@include('emails.header')
<table align="center" style="background:#fff;border-radius:6px;padding:20px;width:600px">
    <tbody>
        <tr>
            <td>
                {{-- <h4>Hello, {{ $employee['first_name'] }} {{ $employee['last_name'] }}</h4> --}}
                <h4>Hello</h4>
                <p>Terima kasih telah mendaftar di FMS! Kami sangat senang Anda bergabung dengan kami.</p>
                <p>Silakan klik tautan di bawah ini untuk memverifikasi alamat email Anda dan menyelesaikan pendaftaran
                    Anda:</p>
                <a style="background-color:#fa5d48;padding:8px 13px;color:white;text-decoration:none;border-radius:5px;margin-top:10px;"
                    href="{{ url('/verify-email/' . $token) }}">Verifikasi Pendaftaran</a>
                <hr style="margin-top:24px;border:0;border-bottom:1px solid #c1c7d0">
                <p>Link ini akan kadaluarsa dalam 1 jam. Jika Anda tidak mendaftar untuk akun ini, silakan abaikan email
                    ini.</p>
                <p>Jika Anda memiliki pertanyaan atau membutuhkan bantuan, jangan ragu untuk menghubungi tim dukungan
                    kami di no wa duluin
                </p>
                <p>Terima kasih.</p>
            </td>
        </tr>
    </tbody>
</table>
@include('emails.footer')
