@include('emails.header')
<table align="center" style="background:#fff;border-radius:6px;padding:20px;width:600px">
    <tbody>
        <tr>
            <td>
                <h4>Hello, {{ $employee['name'] }}</h4>
                <p>Terdapat pengajuan klaim pengeluaran dengan kode <strong>{{ $claimData['code'] }}</strong>.</p>

                {{-- Make me table html --}}
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #000; padding: 8px;">Tanggal</th>
                            <th style="border: 1px solid #000; padding: 8px;">Deskripsi</th>
                            <th style="border: 1px solid #000; padding: 8px;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($claimData['expense_claim_details'] as $item)
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px;">
                                    {{ $item['expense_date'] }}
                                </td>
                                <td style="border: 1px solid #000; padding: 8px;">
                                    {{ $item['description'] }}
                                </td>
                                <td style="border: 1px solid #000; padding: 8px;">
                                    {{ number_format($item['amount'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p>Silahkan melakukan persetujuan/menolak pengajuan lewat aplikasi HRIS</p>
                <hr style="margin-top:24px;border:0;border-bottom:1px solid #c1c7d0">
                <p>Jika Anda memiliki pertanyaan atau membutuhkan bantuan, jangan ragu untuk menghubungi tim dukungan
                    kami di no wa duluin
                </p>
                <p>Terima kasih.</p>
            </td>
        </tr>
    </tbody>
</table>
@include('emails.footer')
