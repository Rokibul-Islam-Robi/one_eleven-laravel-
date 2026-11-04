<label>Full name
    <input name="full_name" value="{{ old('full_name', $address->full_name ?? $accountCustomer->full_name) }}" required maxlength="190">
</label>
<label>Mobile number
    <input type="tel" name="phone" value="{{ old('phone', $address->phone ?? $accountCustomer->phone) }}" required maxlength="40" pattern="(?:\+?880|0)1[3-9][0-9]{8}">
</label>
<label>Division
    <select name="division" required>
        <option value="">Select division</option>
        @foreach ($divisions as $division => $districts)
            <option value="{{ $division }}" @selected(old('division', $address->division ?? '') === $division)>{{ $division }}</option>
        @endforeach
    </select>
</label>
<label>District
    <select name="district" required>
        <option value="">Select district</option>
        @foreach ($divisions as $division => $districts)
            <optgroup label="{{ $division }}">
                @foreach ($districts as $district)
                    <option value="{{ $district }}" @selected(old('district', $address->district ?? '') === $district)>{{ $district }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</label>
<label>Area / City
    <input name="area" value="{{ old('area', $address->area ?? '') }}" required maxlength="120">
</label>
<label>Street address
    <input name="street_address" value="{{ old('street_address', $address->street_address ?? '') }}" required maxlength="255">
</label>
<label>Additional instructions
    <textarea name="instructions" rows="2" maxlength="2000">{{ old('instructions', $address->instructions ?? '') }}</textarea>
</label>
