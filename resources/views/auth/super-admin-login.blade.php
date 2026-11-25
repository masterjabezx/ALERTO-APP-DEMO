@extends('layouts.auth', ['bodyClass' => 'bg-[#17191C] text-[#F5F3EF] antialiased'])

@section('title', 'LDRRMO Super Admin Login — ALERTO APP')

@section('content')
<x-auth.split-login
    variant="super_admin"
    portalTitle="LDRRMO SUPER ADMIN"
    subtitle="Command. Oversight. Accountability."
    welcomeHeading="Executive Authority Sign In"
    welcomeDescription="Municipal emergency governance, routing policies, admin oversight, and cryptographic backups."
    :action="route('super-admin.login.post')"
    loginLabel="Super Admin Email or Phone"
    loginPlaceholder="ldrrmo.head@hinobaan.gov.ph"
    submitLabel="Sign In as LDRRMO Super Admin"
    submittingLabel="Authenticating..."
    securityBanner="LDRRMO SUPER ADMIN — Highest System Governance & Oversight Authority"
    accessNotice="Restricted executive tier. Every login and governance action is cryptographically recorded."
/>
@endsection
