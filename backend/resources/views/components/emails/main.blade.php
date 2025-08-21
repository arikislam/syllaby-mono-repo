<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{config('app.name')}}</title>
    <meta charset="utf-8"/>
</head>
<body style="font-family: 'Mulish', sans-serif; background-color:#D5D9E2;">
<div style="background-color:#ffffff; margin:0 auto; max-width: 600px;">
    <x-emails.header/>
    {{$slot}}
    <x-emails.footer/>
</div>
</body>
</html>
