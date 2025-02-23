<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Http;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use DateTime;

class DataGeneratorController extends Controller
{
    private function getExcelColumn($index) {
        $letters = '';
        while ($index >= 0) {
            $letters = chr($index % 26 + 65) . $letters;
            $index = floor($index / 26) - 1;
        }
        return $letters;
    }

    public function excelAttendanceReport(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $company_id = $request->company_id;
        $filter_date = $request->filterDate;
        $search = $request->search;
        $draw = $request->draw;
        $start = $request->start;
        $length = $request->length;
        $isAll = $request->isAll;



        list($start_date, $end_date) = explode(' - ', $filter_date);

        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);

        $interval = $start_date_obj->diff($end_date_obj);
        $total_days = $interval->days;


         // **1. Buat Header**
        $sheet->setCellValue('A1', 'Attendance Recapitulation');
        $sheet->mergeCells('A1:T1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // **2. Tambahkan Deskripsi**
        $sheet->setCellValue('A2', 'Description');
        $sheet->setCellValue('B2', 'P = On-time Attendance');
        $sheet->setCellValue('B3', '!P = Late Attendance');
        $sheet->setCellValue('B4', '!P!P = Late Attendance for both shifts');

        // **3. Buat Header Recapitulation**
        $sheet->mergeCells('A9:C9');
        $sheet->setCellValue('A9', 'Recapitulation : ' . $filter_date);	
        $sheet->getStyle('A9')->getFont()->setBold(true);
        $sheet->getStyle('A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // **4. Header Warna**
        $headerFill = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF228B22']
            ]
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ];

        // **5. Tambahkan Header Tanggal**
        // $total_days = ambil dari selisih tanggal start_date dan end_date
        $sheet->mergeCells('D9:' . $this->getExcelColumn(3 + $total_days) . '9');
        $sheet->setCellValue('D9', 'Date');
        $sheet->getStyle('D9')->applyFromArray($headerFill);
        $sheet->getStyle('D9')->getFont()->setBold(true);
        $sheet->getStyle('D9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $date = $start_date_obj;

        for ($i = 0; $i <= $total_days; $i++) {
            // $colLetter = chr(68 + $i);
            $colLetter = $this->getExcelColumn(3 + $i);
           
            // Tanggal
            $sheet->setCellValue($colLetter . '10', $i + 1);
            $sheet->getStyle($colLetter . '10')->applyFromArray($headerFill);
            $sheet->getStyle($colLetter . '10')->getFont()->setBold(true);
            $sheet->getStyle($colLetter . '10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // // Hari
            $sheet->setCellValue($colLetter . '11', $date->format('D'));
            $sheet->getStyle($colLetter . '11')->applyFromArray($headerFill);
            $sheet->getStyle($colLetter . '11')->getFont()->setBold(true);
            $sheet->getStyle($colLetter . '11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getColumnDimension($colLetter)->setWidth(6);

            // font color white
            $sheet->getStyle($colLetter . '10')->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($colLetter . '11')->getFont()->getColor()->setARGB('FFFFFFFF');
            $date->modify('+1 day');
        }

        // // **6. Tambahkan Kolom Header**
        $headers = [
            'A' => ['Name', 30],
            'B' => ['Employee ID', 25],
            'C' => ['Department', 25]
        ];

        foreach ($headers as $col => [$title, $width]) {
            $sheet->mergeCells("{$col}10:{$col}11");
            $sheet->setCellValue("{$col}10", $title);
            $sheet->getStyle("{$col}10")->getFont()->setBold(true);
            $sheet->getStyle("{$col}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$col}10")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getColumnDimension($col)->setWidth($width);
            // bg color hijau muda dan text putih
            $sheet->getStyle("{$col}10")->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("{$col}10")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '33cc33']
                ]
            ]);
        }

        // // **7. Tambahkan Kolom Rekapitulasi**
        $summaryColumns = ['Total Attendance', 
        'Total Present',
        'Total Absent', 'Total Leave', 'Total WFH', 'Total Halfday', 
        'Total Late Entry (Minutes)',
        'Early Exit	(Minutes)',
        'Total Working (Hours)'];

        foreach ($summaryColumns as $index => $title) {
            $colLetter = $this->getExcelColumn(4 + $total_days + $index);
            $sheet->mergeCells("{$colLetter}9:{$colLetter}11");
            $sheet->setCellValue("{$colLetter}9", $title);
            $sheet->getStyle("{$colLetter}9")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '00cc66']
                ]
            ]);
            $sheet->getStyle("{$colLetter}9")->getFont()->setBold(true);
            // wrap text
            $sheet->getStyle("{$colLetter}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colLetter}9")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("{$colLetter}9")->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension($colLetter)->setWidth(12);
        }

        // // **8. Tambahkan Data Presensi Report & Summary**
        $requestData = [
            "draw" => $draw,
            "start" => $start,
            "length" => $length,
            "order" => [
                [
                    "column" => 0,
                    "dir" => "DESC"
                ]
            ],
            "search" => $search,
            "company_id" => $company_id,
            "filter_date" => $filter_date,
        ];

        $headers = [
            'accept' => 'application/json',
            'Authorization' => 'Bearer ' . config('apiendpoints.LOCAL_API_ATTENDANCES_KEY')
        ];


        // **8. Tambahkan Data Presensi Report & Summary**

        // per 100 data
        $totalData = 100;
        $dataPresence = [];
        $dataSummary = [];
        $dataPresenceSET = [];
        
        // Ambil data awal
        $responsePresence = Http::withHeaders($headers)->
        post(config('apiendpoints.LOCAL_API_ATTENDANCES') . '/api/v1/attendance/report/by',
         $requestData);

        $responseResume = Http::withHeaders($headers)->
        post(config('apiendpoints.LOCAL_API_ATTENDANCES') . '/api/v1/attendance/report/summary',
         $requestData);
         
        if ($responsePresence->status() == 200) {
            $dataPresence = $responsePresence->json();
            $dataSummary = $responseResume->json();
        } else {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $responsePresence->json()
            ], 500);
        }

        for ($i = 0; $i < count($dataPresence['data']); $i++) {
            // posisi ini saya cuman ambil nama department
            $temp = [
                // topper
                ucwords($dataPresence['data'][$i]['first_name'] . ' ' . $dataPresence['data'][$i]['last_name']),
                $dataPresence['data'][$i]['employee_id'],
                $dataPresence['data'][$i]['department_id'] != null ? $dataPresence['data'][$i]['department_id'] : ''
            ];

            // sekarang looping untuk absensi
            for ($j =1; $j < count($dataPresence['data'][$i]['attendances']) +1; $j++) {
                $temp[] = $dataPresence['data'][$i]['attendances'][$j]['status'] ?? '';
            }

            // ambil data summary
            $temp[] = "'" . (
            $dataSummary['data'][$i]['total_present'] + 
            $dataSummary['data'][$i]['total_absent'] + 
            $dataSummary['data'][$i]['total_leave'] + 
            $dataSummary['data'][$i]['total_wfh'] + 
            $dataSummary['data'][$i]['total_halfday']);

            $temp[] = "'" . $dataSummary['data'][$i]['total_present'];
            $temp[] = "'" . $dataSummary['data'][$i]['total_absent'];
            $temp[] = "'" . $dataSummary['data'][$i]['total_leave'];
            $temp[] = "'" . $dataSummary['data'][$i]['total_wfh'];
            $temp[] = "'" . $dataSummary['data'][$i]['total_halfday'];
            $temp[] = "'" . $dataSummary['data'][$i]['late_entry_sum'] . " minutes (" . $dataSummary['data'][$i]['total_late_entry'] . ")'";
            $temp[] = "'" . $dataSummary['data'][$i]['total_early_exit'];
            $temp[] = "'" . $dataSummary['data'][$i]['total_working_hours'];



            $dataPresenceSET[] = $temp;
        }
        
        $startRow = 12;
        $sheet->fromArray($dataPresenceSET, null, "A{$startRow}");

        // Menentukan jumlah baris dan kolom data absensi
        $lastRow = $sheet->getHighestRow();

        for ($row = $startRow; $row <= $lastRow; $row++) {
            for ($col = 4; $col <= $total_days + 4; $col++) {
                // Menentukan alamat sel dengan benar
                $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                $cell = $sheet->getCell($cellAddress);
                $value = $cell->getValue();

                // Pusatkan teks di dalam sel
                $style = $cell->getStyle();
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Mapping warna berdasarkan status
                $colorMapping = [
                    'P'   => 'FF228B22', // Hijau (Green)
                    'A'   => 'FFFF0000', // Merah (Red)
                    '!'   => 'cc9900', // Kuning (Yellow)
                    'L'   => 'cc9900', // Kuning (Yellow)
                    'H'   => 'FF00FFFF', // Biru Muda (Light Blue)
                    'WFH' => 'FF0000FF', // Biru (Blue)
                    '?'   => 'cc9900'  // Kuning (Yellow)
                ];

                if (!empty($value)) {
                    $richText = new RichText();

                    // Loop setiap karakter dalam status untuk diberikan warna
                    for ($i = 0; $i < strlen($value); $i++) {
                        if ($value[$i] != '-') {
                            $currentStatus = $value[$i];
                            $color = $colorMapping[$currentStatus] ?? 'FF000000'; // Default Hitam jika tidak ditemukan

                            // Tambahkan teks dengan warna spesifik
                            $textRun = $richText->createTextRun($currentStatus);
                            $textRun->getFont()->getColor()->setARGB($color);
                        } else {
                            // Tambahkan teks dengan warna spesifik
                            $textRun = $richText->createTextRun($value[$i]);
                        }
                    }

                    // Set nilai ke sel dengan format teks warna-warni
                    $cell->setValue($richText);
                }
            }
        }
        
        // // **10. Terapkan Border ke Semua Sel**
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
        $range = "A1:{$lastColumn}{$lastRow}";
        $sheet->getStyle($range)->applyFromArray($borderStyle);
        

        // **11. Download File**
        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'attendance.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="attendance.xlsx"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);        
    }
}
