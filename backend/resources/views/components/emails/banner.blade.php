@props([
    'name' => '',
    'image' => 'banner.png',
])

<table style="padding: 0; border-collapse: collapse;">
    <tr>
        <td style="background: #854EFF; width: 60%;">
            <p style="font-size: 27px; color: #fff; padding-left: 60px; padding-right: 40px;">Hi
                <br/> {{$name}},</p>
        </td>
        <td style='padding: 0; width: 40%;'>
            <x-emails.image imageName="{{$image}}"/>
        </td>
    </tr>
</table>