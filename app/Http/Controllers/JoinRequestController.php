<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class JoinRequestController extends Controller
{
    /**
     * Display the home page with the join request form.
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Handle the join request form submission.
     */
    public function store(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'has_github' => 'required|in:yes,no',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'reason' => 'required|in:learn,improve,collaborate,other',
            'other_reason' => 'required_if:reason,other|string|max:255',
            'message' => 'nullable|string|max:1000',
        ], [
            'full_name.required' => 'Full name is required.',
            'has_github.required' => 'Please specify if you have a GitHub account.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'reason.required' => 'Please select a reason for joining.',
            'other_reason.required_if' => 'Please specify your reason when selecting "Other".',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Prepare the email data
        $emailData = [
            'full_name' => $request->full_name,
            'has_github' => $request->has_github,
            'email' => $request->email,
            'phone' => $request->phone,
            'reason' => $request->reason,
            'other_reason' => $request->other_reason,
            'message' => $request->message,
        ];

        try {
            // Send email notification
            Mail::send('emails.join-request', $emailData, function ($message) use ($emailData) {
                $message->to('dtavares86@gmail.com')
                    ->subject('New AuthHub Project Join Request')
                    ->replyTo($emailData['email'], $emailData['full_name']);
            });

            return redirect()->back()->with('success', 'Your request has been submitted successfully! We\'ll get back to you soon.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['email' => 'There was an error sending your request. Please try again later.'])
                ->withInput();
        }
    }
}