{{-- Liste déroulante des lieux, groupée « Chambres » / « Espaces communs ».
     Attend $roomGroups (Room::groupedForSelect()), $selected et $placeholder. --}}
<select id="{{ $id ?? 'room_id' }}" name="{{ $name ?? 'room_id' }}" class="mt-1 block w-full border-line rounded-md shadow-sm">
    <option value="">{{ $placeholder }}</option>
    @foreach ($roomGroups as $group => $rooms)
        @if ($rooms->isNotEmpty())
            <optgroup label="{{ $group }}">
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected((string) $selected === (string) $room->id)>
                        {{ $room->label }}@if ($room->status === 'hors_service') (hors service)@endif
                    </option>
                @endforeach
            </optgroup>
        @endif
    @endforeach
</select>
