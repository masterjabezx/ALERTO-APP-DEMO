@extends('layouts.auth', ['bodyClass' => 'bg-[#101214] text-[#F5F3EF] antialiased'])

@section('title', 'Personnel Portal Login — ALERTO APP')

@section('content')
<x-auth.split-login
    variant="personnel"
    portalTitle="PERSONNEL PORTAL"
    subtitle="Your response. Your commitment. Our community."
    welcomeHeading="Responder Sign In"
    welcomeDescription="Access field dispatches, live incident map, and tactical updates."
    :action="route('personnel.login')"
    loginLabel="Email or Phone Number"
    loginPlaceholder="responder@hinobaan.gov.ph or 09XXXXXXXXX"
    submitLabel="Sign In to Personnel Portal"
    submittingLabel="Authenticating..."
    accessNotice="Personnel accounts are provisioned by your Office Admin or LDRRMO administrator. All access is logged."
/>
@endsection
