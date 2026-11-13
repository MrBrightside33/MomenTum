@props(['rows' => 5])

<x-sk.table columns="5" rows="{{ $rows ?? 5 }}" />
