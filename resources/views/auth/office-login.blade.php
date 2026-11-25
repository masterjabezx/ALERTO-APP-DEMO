@extends('layouts.auth', ['bodyClass' => 'bg-[#F5F3EF] text-[#17191C] antialiased'])

@section('title', 'Office Portal Login — ALERTO APP')

@section('content')
<x-auth.split-login
    variant="office"
    portalTitle="OFFICE PORTAL"
    subtitle="Connected offices. Coordinated response."
    welcomeHeading="Agency Desk Sign In"
    welcomeDescription="Coordinate agency intake, dispatch teams, and track municipal responses."
    :action="route('office.login')"
    loginLabel="Email or Phone Number"
    loginPlaceholder="office@agency.gov.ph or 09XXXXXXXXX"
    submitLabel="Sign In to Office Portal"
    submittingLabel="Authenticating..."
    accessNotice="Office accounts are provisioned by DIAL DAPH Command Center & LDRRMO Super Admin."
/>
@endsection
