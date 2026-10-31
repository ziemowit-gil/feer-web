@php $v = fn ($k, $d = '') => old($k, $e?->{$k} ?? $d); @endphp
<div class="mt-3 grid gap-4 sm:grid-cols-2">
    <div><label for="{{ $uid }}-starts" class="{{ $lbl }}">Start *</label><input id="{{ $uid }}-starts" type="date" name="starts_on" required value="{{ $e ? $e->starts_on->toDateString() : old('starts_on') }}" class="{{ $in }}"></div>
    <div><label for="{{ $uid }}-ends" class="{{ $lbl }}">Koniec</label><input id="{{ $uid }}-ends" type="date" name="ends_on" value="{{ $e?->ends_on ? $e->ends_on->toDateString() : old('ends_on') }}" class="{{ $in }}"></div>
    <div><label for="{{ $uid }}-sch" class="{{ $lbl }}">Harmonogram</label><input id="{{ $uid }}-sch" name="schedule" placeholder="poniedziałki 17:00–19:00" value="{{ $v('schedule') }}" class="{{ $in }}"></div>
    <div><label for="{{ $uid }}-inst" class="{{ $lbl }}">Prowadzący</label><input id="{{ $uid }}-inst" name="instructor" value="{{ $v('instructor') }}" class="{{ $in }}"></div>
    <div><label for="{{ $uid }}-loc" class="{{ $lbl }}">Miejsce</label><input id="{{ $uid }}-loc" name="location" value="{{ $v('location') }}" class="{{ $in }}"></div>
    <div><label for="{{ $uid }}-url" class="{{ $lbl }}">Link do spotkania</label><input id="{{ $uid }}-url" type="url" name="meeting_url" value="{{ $v('meeting_url') }}" class="{{ $in }}"></div>
    <div><label for="{{ $uid }}-cap" class="{{ $lbl }}">Limit miejsc *</label><input id="{{ $uid }}-cap" type="number" min="0" name="capacity" required value="{{ $v('capacity', 10) }}" class="{{ $in }}"></div>
    <div><label for="{{ $uid }}-enr" class="{{ $lbl }}">Rekrutacja *</label>
        <select id="{{ $uid }}-enr" name="enrollment_status" class="{{ $in }}">@foreach (\App\Models\CourseEdition::ENROLLMENT as $k => $l)<option value="{{ $k }}" @selected($v('enrollment_status', 'open') === $k)>{{ $l }}</option>@endforeach</select></div>
</div>
