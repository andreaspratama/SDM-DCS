@foreach($data as $employee_id => $items)

<tr>
    <td>{{ $items->first()->employee->nama }}</td>
    <td>{{ $items->count() }} hari</td>
    <td>{{ $items->sum('late_minutes') }} menit</td>
    <td>
        <a href="{{ route('absensi.detailRange', $employee_id) }}">
            Detail
        </a>
    </td>
</tr>

@endforeach