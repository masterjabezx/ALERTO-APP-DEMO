@extends('layouts.auth', ['bodyClass' => 'bg-[#17191C] text-[#F5F3EF] antialiased'])

@section('title', 'DIAL DAPH Command Center Login — ALERTO APP')

@section('content')
<x-auth.split-login
    variant="admin"
    portalTitle="DIAL DAPH COMMAND CENTER"
    subtitle="Every second matters. Every response counts."
    welcomeHeading="Command Operator Sign In"
    welcomeDescription="Emergency receiving, call verification, incident assessment, and multi-agency dispatch."
    :action="route('admin.login')"
    loginLabel="Admin Email or Phone Number"
    loginPlaceholder="operator@hinobaan.gov.ph"
    submitLabel="Access Control Center"
    submittingLabel="Verifying Credentials..."
    securityBanner="Restricted Access — DIAL DAPH Command Center Operators Only"
    accessNotice="Provisioned by LDRRMO Super Admin. All operational sessions are strictly audited."
/>
@endsection
