<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Logbook Bimbingan</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        p { margin: 0 0 4px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 10px 12px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        small { color: #6b7280; }
        @media print { body { margin: 12px; } }
    
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        . { margin-bottom: 1rem; }
        .btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            padding: 0.5rem 1rem; 
            border-radius: 6px; 
            font-weight: 600; 
            text-decoration: none; 
            font-size: 0.875rem; 
            cursor: pointer;
        }
        .btn-primary { 
            background: #16a34a; 
            color: #fff; 
            border: 1px solid #16a34a; 
        }
        .btn-primary:hover { background: #15803d; }
        .btn { min-width: 9rem; justify-content: center; }
        .btn svg { width: 0.9rem; height: 0.9rem; flex: 0 0 0.9rem; }
        @media print { 
            .btn-primary { display: none !important; } 
            body { margin: 12px; } 
        }
</style>
</head>
<body onload="window.print()">
    <div class="flex justify-between items-center ">
        <div>
            <h1>Logbook Bimbingan</h1>
            <p>{{ $skripsi->student?->name ?? '-' }} • {{ $skripsi->title }}</p>
            <p>{{ $selectedReviewer?->name ? 'Filter Dosen: ' . $selectedReviewer->name : 'Semua Dosen' }}</p>
        </div>
        <a href="javascript:window.print();" class="btn btn-primary">@include("partials.icons.print")<span>Cetak PDF</span></a>
    </div>

    <table>
        <colgroup>
            <col style="width: 14%;">
            <col style="width: 18%;">
            <col style="width: 52%;">
            <col style="width: 16%;">
        </colgroup>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Dosen</th>
                <th>Catatan</th>
                <th>File</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        {{ $row->meeting_date?->format('d/m/Y') ?? '-' }}<br>
                        <small>{{ $row->created_at?->format('H:i') ?? '-' }}</small>
                    </td>
                    <td>{{ $row->reviewer?->name ?? '-' }}</td>
                    <td>{{ $row->student_notes ?: ($row->lecturer_notes ?: '-') }}</td>
                    <td>
                        @if($row->has_revision_file)
                            @php
                                $fileName = $row->reviewedVersion?->file_path ? basename($row->reviewedVersion->file_path) : 'Dokumen';
                                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                                if (preg_match('/revision_v\d+_/i', $fileName, $match)) {
                                    $displayFileName = '...' . $match[0] . '...' . ($ext ? '.' . $ext : '');
                                } elseif (mb_strlen($fileName) > 24) {
                                    $displayFileName = '...' . mb_substr($fileName, 0, 8) . '...' . ($ext ? '.' . $ext : '');
                                } else {
                                    $displayFileName = $fileName;
                                }
                            @endphp
                            {{ $displayFileName }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada histori bimbingan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
