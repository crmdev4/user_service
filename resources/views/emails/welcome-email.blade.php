@include('emails.header')
<table align="center" style="background:#fff;border-radius:6px;padding:20px;width:600px">
    <tbody>
        <tr>
            <td>
                <h4>Hello {{ $userData['first_name'] }}!</h4>
                <p>Terima kasih telah mendaftar di FMS! Kami sangat senang Anda bergabung dengan kami.</p>
            </td>
        </tr>
    </tbody>
</table>
@include('emails.footer')
