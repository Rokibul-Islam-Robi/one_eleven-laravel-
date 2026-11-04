<label>Full name
    <input name="full_name" value="{{ old('full_name', $patient->full_name ?? '') }}" required maxlength="190">
</label>
<label>Date of birth
    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', isset($patient->date_of_birth) ? \Illuminate\Support\Carbon::parse($patient->date_of_birth)->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}">
</label>
<label>Gender
    <select name="gender">
        <option value="">Select</option>
        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
            <option value="{{ $value }}" @selected(old('gender', $patient->gender ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label>Relationship to customer
    <input name="relationship" value="{{ old('relationship', $patient->relationship ?? '') }}" required maxlength="80" placeholder="e.g. Self, child, parent">
</label>
