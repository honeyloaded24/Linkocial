@extends('layouts.marketing')

@section('header_title', 'Terms of Service')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="premium-card p-5 shadow-sm">
                <div class="text-center mb-5">
                    <h1 class="fw-bold display-5">Terms of Service</h1>
                    <p class="text-muted">Last Updated: {{ date('F d, Y') }}</p>
                </div>

                <div class="lh-lg text-secondary">
                    <h4 class="text-primary-custom fw-bold mt-4">1. General</h4>
                    <p>By placing an order with Linkocial, you automatically accept all the below-listed terms of service whether you read them or not. We reserve the right to change these terms of service without notice. You are expected to read all terms of service before placing every order to ensure you are up to date with any changes or any future changes.</p>

                    <h4 class="text-primary-custom fw-bold mt-4">2. Service Usage</h4>
                    <p>Linkocial will only be used for promotional purposes for your account and inventory on Social Media. We do not guarantee your new followers will interact with you, we simply guarantee you to get the followers you pay for. You will not upload anything into the Linkocial site including nudity or any material that is not accepted or suitable for the social media community.</p>

                    <h4 class="text-primary-custom fw-bold mt-4">3. Refund Policy</h4>
                    <p>No refunds will be made to your payment method. After a deposit has been completed, there is no way to reverse it. You must use your balance on orders from Linkocial. You agree that once you complete a payment, you will not file a dispute or a chargeback against us for any reason. If you file a dispute or chargeback against us after a deposit, we reserve the right to terminate all future orders and ban you from our site.</p>

                    <h4 class="text-primary-custom fw-bold mt-4">4. Privacy Policy</h4>
                    <p>This policy covers how we use your personal information. We take your privacy seriously and will take all measures to protect your personal information. Any personal information received will only be used to fill your order. We will not sell or redistribute your information to anyone.</p>

                    <h4 class="text-primary-custom fw-bold mt-4">5. Disclaimer</h4>
                    <p>Linkocial is not responsible for any account suspension or picture deletion done by Instagram, Facebook, Twitter, YouTube or other social media. Use of our services is at your own risk.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
