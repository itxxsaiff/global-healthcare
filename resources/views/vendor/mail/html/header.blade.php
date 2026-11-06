@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ url('/icons/icon-192.png') }}" width="34" height="34" alt="" style="vertical-align: middle; border-radius: 9px; margin-right: 8px;">
<span style="vertical-align: middle;">{{ config('app.name') }}</span>
</a>
</td>
</tr>
