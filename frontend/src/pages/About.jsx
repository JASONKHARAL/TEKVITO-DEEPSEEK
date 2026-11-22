import { Link } from 'react-router-dom'

export default function About() {
  return (
    <div className="about-page">

      {/* Hero */}
      <section className="about-hero">
        <div className="about-hero-inner">
          <div className="hero-badge">Our story</div>
          <h1 className="about-title">
            We're building the <span className="hero-gradient">future of hiring</span>
          </h1>
          <p className="about-subtitle">
            TEKVITO connects candidates and employers with a clean, fair, and fast hiring experience.
            No noise. No spam. Just real opportunities and real talent.
          </p>
        </div>
      </section>

      {/* Mission */}
      <section className="about-section">
        <div className="about-mission">
          <h2>Our mission</h2>
          <p>
            Job hunting should not feel like throwing resumes into a void. Hiring should not feel
            like searching for a needle in a haystack. TEKVITO exists to make the process
            <strong> transparent, human, and efficient </strong>
            for both sides.
          </p>
          <p>
            We believe great matches come from clarity — clear profiles, clear job descriptions,
            and clear communication at every step. That's why every feature we build starts
            with a simple question: <em>does this make it easier for someone to find the right fit?</em>
          </p>
        </div>
      </section>

      {/* How it works */}
      <section className="about-section">
        <h2 className="about-section-title">How it works</h2>
        <div className="about-columns">

          <div className="about-column">
            <div className="about-column-header">
              <span className="about-column-icon">👤</span>
              <h3>For candidates</h3>
            </div>
            <ol className="about-steps">
              <li>
                <strong>Build your profile</strong>
                <span>Add your skills, experience, and upload your resume.</span>
              </li>
              <li>
                <strong>Discover jobs</strong>
                <span>Search and filter by skill, category, or location.</span>
              </li>
              <li>
                <strong>Apply in one click</strong>
                <span>Your resume attaches automatically. Add a cover letter if you want.</span>
              </li>
              <li>
                <strong>Track your progress</strong>
                <span>Watch your application move through each hiring stage.</span>
              </li>
            </ol>
          </div>

          <div className="about-column">
            <div className="about-column-header">
              <span className="about-column-icon">🏢</span>
              <h3>For employers</h3>
            </div>
            <ol className="about-steps">
              <li>
                <strong>Post a job</strong>
                <span>Create a listing in minutes. Add required skills for better matching.</span>
              </li>
              <li>
                <strong>Review candidates</strong>
                <span>See applicant profiles, resumes, and skills side by side.</span>
              </li>
              <li>
                <strong>Move them forward</strong>
                <span>Shortlist, schedule interviews, and send offers from one place.</span>
              </li>
              <li>
                <strong>Hire with confidence</strong>
                <span>Every change notifies the candidate instantly.</span>
              </li>
            </ol>
          </div>

        </div>
      </section>

      {/* Values */}
      <section className="about-section">
        <h2 className="about-section-title">What we care about</h2>
        <div className="about-values">
          <div className="about-value">
            <div className="about-value-icon">🎯</div>
            <h3>Clarity over complexity</h3>
            <p>Every screen should answer one question at a glance. No dark patterns, no noise.</p>
          </div>
          <div className="about-value">
            <div className="about-value-icon">🤝</div>
            <h3>Respect both sides</h3>
            <p>Candidates get real feedback. Employers get real signals. Everyone saves time.</p>
          </div>
          <div className="about-value">
            <div className="about-value-icon">🔒</div>
            <h3>Privacy by default</h3>
            <p>Your data stays yours. Resumes are only shared when you apply.</p>
          </div>
          <div className="about-value">
            <div className="about-value-icon">⚡</div>
            <h3>Fast by design</h3>
            <p>No 10-field forms. No 3-week response times. Apply, review, respond — quickly.</p>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="about-cta">
        <h2>Ready to find your fit?</h2>
        <p>Join thousands of candidates and employers already using TEKVITO.</p>
        <div className="about-cta-actions">
          <Link to="/jobs" className="btn btn-primary">Browse Jobs</Link>
          <Link to="/register" className="btn btn-secondary">Create Account</Link>
        </div>
      </section>

    </div>
  )
}