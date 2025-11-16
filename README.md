# OpportunityOS - AI-Powered Email Opportunity Scanner

OpportunityOS is an intelligent email scanning system that automatically classifies emails from your Gmail inbox into opportunities: job postings, funding announcements, and consulting leads.

## Features

- **Gmail Integration**: Secure OAuth authentication with Gmail
- **AI Classification**: Uses Claude AI to intelligently classify emails
- **Opportunity Types**:
  - Job opportunities (keywords: hiring, role, position, career)
  - Funding announcements (keywords: raised, funding, series, investment)
  - Consulting leads (keywords: CRM, transformation, AI project)
- **Relevance Scoring**: Each opportunity is scored 1-10 based on relevance
- **Dashboard UI**: Clean interface to scan emails and view opportunities
- **SQLite Database**: Persistent storage of discovered opportunities

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: SQLite (PDO)
- **AI**: Anthropic Claude API
- **Email**: Google Gmail API with OAuth 2.0
- **Frontend**: Vanilla JavaScript with modern CSS

## Prerequisites

1. **PHP 7.4+** with PDO SQLite extension
2. **Composer** for dependency management
3. **Google Cloud Project** with Gmail API enabled
4. **Anthropic API Key** from [console.anthropic.com](https://console.anthropic.com/)

## Setup Instructions

### 1. Clone and Install Dependencies

```bash
git clone https://github.com/jaykalam/opportunityos-main.git
cd opportunityos-main
composer install