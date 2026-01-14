# USA Funding Applications - Site Analysis

## Overview
The target site (`https://usafundingapplications.org`) is a lead generation and funding application assistance platform. It uses a high-conversion funnel to guide users through qualifying questions before directing them to a paid service or registration area.

## Purpose
The site serves as a **Funding Application Wizard**. It attracts users searching for grants/loans (Business, Personal, Housing, etc.) and filters them through a qualification process. The ultimate goal appears to be converting these leads into paying members for a "Research Service" or "Funding Database".

## Key Stages (The Funnel)

### 1. Landing Page (The Hook)
*   **Hero Section**: High urgency messaging ("Applications are NOW Available!").
*   **Mechanism**:
    *   **Step 1**: Funding Amount Selector (Micro-commitment).
    *   **Step 2**: category Selector (Business, Personal, Real Estate, etc.).
*   **Visuals**: Clean, trust-building icons and green/blue color scheme (associated with money/finance).

### 2. Registration Form (Data Collection)
*   **Purpose**: Qualify the lead and capture contact info.
*   **Data Points Collected**:
    *   **Intent**: Funding Category (Business, Education, etc.).
    *   **Profile**: Citizenship Status, Age Range, Gender, Ethnicity.
    *   **Location**: Zip Code (Auto-resolved to City/State).
    *   **Contact**: Name, Email, Phone.
*   **Tech Stack Notes**: Uses AJAX for Zip-to-City lookup and Email validation.

### 3. Research Service / Order Page (The Product)
*   **Concept**: After registration, users are likely presented with a "Research Service" offer. 
*   **Value Proposition**: Access to a curated database of funding applications, expert assistance, or a "guarantee" of finding a match.
*   **Monetization**: Likely a one-time fee or subscription model to access the "Members Area".

### 4. Members Area (Fulfillment)
*   **Access**: Restricted to registered/paid users.
*   **Content**: Dashboard with lists of available grants, application tracking, and support.

## Feature Requirements for NationalResourceBenefits

To replicate and enhance this platform, we need the following features in our Laravel/Filament/Vue application:

### Frontend (Public)
1.  **Dynamic Wizard**: 
    *   Multi-step form (Amount -> Category -> Personal Details).
    *   Smooth transitions and validation.
2.  **CMS/Content**:
    *   Ability to manage "Funding Categories" and "Amount Ranges" from Admin.
3.  **Authentication**:
    *   Registration aligned with the wizard flow (auto-register upon form completion).

### Backend (Admin - Filament)
1.  **Lead Management**:
    *   View all "Incomplete" and "Completed" applications.
    *   Filter by state, funding type, or amount.
2.  **Product/Order Management**:
    *   Define access levels (Free vs. Paid).
    *   Process payments (already partially implemented with Wallet/Stripe?).
3.  **Funding Source Database**:
    *   A CRUD resource to manage the "Grants" or "Loans" that users are paying to see.

### User Dashboard (Post-Login)
1.  **Recommended Funding**: Show grants matching their profile (from the wizard).
2.  **Application Status**: Track where they have applied.
3.  **Support**: Access to help (Support Tickets already exists).

## Next Steps
1.  [Frontend] Refine the `Welcome.vue` to fully match the scraped "Wizard" flow (already started).
2.  [Backend] Create a `FundingApplication` model to store the wizard data.
3.  [Backend] Create a `FundingSource` model (the product).
4.  [Admin] Build Filament resources for the above.
