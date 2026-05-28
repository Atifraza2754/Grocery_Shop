@php
    $rowCount = count($memo['rows']);
    // Pad short memos with blank rows for some writing space.
    $minRows  = 6;
@endphp

<div class="memo">
    <div class="title">Bill/CASH MEMO</div>

    <table class="hdr">
        <tr>
            <td class="h-cat">
                <div class="field"><span class="lbl">Category:</span> {{ $memo['category'] }}</div>
            </td>
            <td class="h-date">
                <div class="field"><span class="lbl">Date:</span> &nbsp;</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="field"><span class="lbl">Shop:</span> &nbsp;</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="c-sno">S.No.</th>
                <th class="c-desc">Description</th>
                <th class="c-qty">Qty.</th>
                <th class="c-price">Unit Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($memo['rows'] as $row)
                <tr>
                    <td class="c-sno">{{ $row['sno'] }}</td>
                    <td class="c-desc {{ $row['sub'] ? 'sub' : '' }}">{{ $row['desc'] }}</td>
                    <td class="c-qty">{{ $row['qty'] }}</td>
                    <td class="c-price">&nbsp;</td>
                </tr>
            @endforeach

            @for ($i = $rowCount; $i < $minRows; $i++)
                <tr class="filler">
                    <td class="c-sno">&nbsp;</td>
                    <td class="c-desc"></td>
                    <td class="c-qty"></td>
                    <td class="c-price"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- Total sits OUTSIDE the items cells, in its own border at the
         very bottom of each memo. --}}
    <table class="total-box">
        <tr>
            <td class="t-label">Total</td>
            <td class="t-val">&nbsp;</td>
        </tr>
    </table>
</div>
