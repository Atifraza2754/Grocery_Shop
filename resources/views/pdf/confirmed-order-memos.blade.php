<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm; }

        * { box-sizing: border-box; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
        }

        table.grid { width: 100%; border-collapse: collapse; }
        table.grid td.cell {
            width: 50%;
            vertical-align: top;
            padding: 2mm;       /* gap between memos */
        }

        /* ---- Single memo (its own border, hugs its content) ---- */
        .memo {
            border: 1px solid #000;
            padding: 2.5mm;
        }

        .memo .title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            text-decoration: underline;
            margin-bottom: 2mm;
        }

        .memo .hdr { width: 100%; margin-bottom: 1.5mm; border-collapse: collapse; }
        .memo .hdr td { vertical-align: top; }
        .memo .hdr td.h-cat  { width: 60%; padding-right: 3mm; }
        .memo .hdr td.h-date { width: 40%; }
        .memo .field {
            border-bottom: 1px solid #000;
            padding: 1px 0;
            margin-top: 3px;
            font-size: 11px;
        }
        .memo .field .lbl { font-weight: bold; }

        /* ---- Items table ---- */
        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 11px;
            vertical-align: top;
        }
        table.items th { font-weight: bold; text-align: left; }

        .c-sno   { width: 12%; }
        .c-desc  { width: 50%; }
        .c-qty   { width: 18%; }
        .c-price { width: 20%; text-align: right; }

        td.c-desc.sub { padding-left: 12px; }
        tr.filler td { height: 10px; }

        /* ---- Total: its own bordered box BELOW the items table ---- */
        table.total-box {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            border-top: 0;            /* sits flush under the items table */
        }
        table.total-box td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 11px;
            vertical-align: top;
        }
        table.total-box td.t-label { width: 80%; text-align: right; font-weight: bold; }
        table.total-box td.t-val   { width: 20%; text-align: right; }

        .memo .words { margin-top: 1.5mm; font-weight: bold; font-size: 11px; }
        .memo .words .ln {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 55%;
        }
    </style>
</head>
<body>
@php
    // 2 memos per row; rows flow down the page and break across pages
    // naturally (memos are per-category and can be any height).
    $rows = array_chunk($memos, 2);
@endphp

<table class="grid">
    @foreach ($rows as $row)
        <tr>
            @for ($c = 0; $c < 2; $c++)
                <td class="cell">
                    @isset($row[$c])
                        @include('pdf.partials.memo', ['memo' => $row[$c]])
                    @endisset
                </td>
            @endfor
        </tr>
    @endforeach
</table>
</body>
</html>
