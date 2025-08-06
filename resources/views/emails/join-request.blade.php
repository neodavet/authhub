<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New AuthHub Join Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #ef4444;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .field {
            margin-bottom: 15px;
        }
        .field label {
            font-weight: bold;
            color: #374151;
        }
        .field-value {
            margin-top: 5px;
            padding: 8px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .reason-badge {
            display: inline-block;
            background-color: #ef4444;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .github-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .github-yes {
            background-color: #10b981;
            color: white;
        }
        .github-no {
            background-color: #f59e0b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 New AuthHub Join Request</h1>
    </div>
    
    <div class="content">
        <p>You have received a new join request for the AuthHub project:</p>
        
        <div class="field">
            <label>👤 Full Name:</label>
            <div class="field-value">{{ $full_name }}</div>
        </div>
        
        <div class="field">
            <label>📧 Email:</label>
            <div class="field-value">{{ $email }}</div>
        </div>
        
        <div class="field">
            <label>📱 Phone:</label>
            <div class="field-value">{{ $phone }}</div>
        </div>
        
        <div class="field">
            <label>🐙 GitHub Account:</label>
            <div class="field-value">
                <span class="github-status {{ $has_github === 'yes' ? 'github-yes' : 'github-no' }}">
                    {{ $has_github === 'yes' ? 'Yes' : 'No' }}
                </span>
            </div>
        </div>
        
        <div class="field">
            <label>🎯 Reason for Joining:</label>
            <div class="field-value">
                <span class="reason-badge">
                    @switch($reason)
                        @case('learn')
                            Learn PHP & Laravel
                            @break
                        @case('improve')
                            Improve PHP & Laravel Skills
                            @break
                        @case('collaborate')
                            I want to collaborate
                            @break
                        @case('other')
                            Other: {{ $other_reason }}
                            @break
                        @default
                            {{ $reason }}
                    @endswitch
                </span>
            </div>
        </div>
        
        @if($message)
        <div class="field">
            <label>💬 Additional Message:</label>
            <div class="field-value">{{ $message }}</div>
        </div>
        @endif
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        
        <p style="text-align: center; color: #6b7280; font-size: 14px;">
            This email was sent from the AuthHub project website.<br>
            Reply directly to this email to contact the applicant.
        </p>
    </div>
</body>
</html>