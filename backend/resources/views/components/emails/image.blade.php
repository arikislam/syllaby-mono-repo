@props([
    'imageName' => '',
    'alt' => config('app.name')
])
<img src="{{'cid:'. $imageName }}" alt="{{ $alt }}" style="max-width: 475px">
