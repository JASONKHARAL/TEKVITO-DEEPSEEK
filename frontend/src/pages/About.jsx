
import { Link } from 'react-router-dom'
import {
  ArrowRight,
  Globe2,
  Users,
  BriefcaseBusiness,
  Network,
  Target,
  ShieldCheck,
  Handshake,
  Lightbulb,
  Building2,
  CheckCircle2,
  Compass,
  Sparkles,
} from 'lucide-react'

const services = [
  {
    icon: BriefcaseBusiness,
    number: '01',
    title: 'IT Staffing',
    description:
      'Connecting businesses with technology professionals for contract and project-based workforce needs.',
  },
  {
    icon: Users,
    number: '02',
    title: 'Permanent Recruitment',
    description:
      'Helping organizations identify and connect with professionals who match their long-term hiring needs.',
  },
  {
    icon: Network,
    number: '03',
    title: 'C2C & Contract Staffing',
    description:
      'Supporting contract-to-contract engagements and flexible staffing relationships through business partnerships.',
  },
  {
    icon: Handshake,
    number: '04',
    title: 'Recruitment Partnerships',
    description:
      'Working with direct clients and recruitment partners to build effective talent-sourcing relationships.',
  },
]

const values = [
  {
    icon: Target,
    title: 'Purposeful connections',
    description:
      'We focus on bringing the right people and opportunities together through thoughtful recruitment.',
  },
  {
    icon: ShieldCheck,
    title: 'Trust and transparency',
    description:
      'Clear communication, professional conduct and dependable working relationships guide our approach.',
  },
  {
    icon: Lightbulb,
    title: 'Continuous improvement',
    description:
      'We believe recruitment should keep evolving with technology, business needs and the people we serve.',
  },
  {
    icon: Users,
    title: 'People first',
    description:
      'Behind every requirement and application is a person. We believe every interaction deserves respect.',
  },
]

export default function About() {
  return (
    <main className="tk-about">
      <style>{`
        .tk-about {
          --tk-navy: #07152b;
          --tk-blue: #2563eb;
          --tk-ink: #10213b;
          --tk-muted: #64748b;
          --tk-line: #e5eaf2;
          color: var(--tk-ink);
          background: #fff;
          overflow: hidden;
        }

        .tk-about * { box-sizing: border-box; }
        .tk-about a { text-decoration: none; }

        .tk-container {
          width: min(1160px, calc(100% - 48px));
          margin: 0 auto;
        }

        .tk-eyebrow {
          display: inline-flex;
          align-items: center;
          gap: 9px;
          color: #2563eb;
          font-size: 12px;
          font-weight: 800;
          letter-spacing: 2px;
          text-transform: uppercase;
        }

        .tk-eyebrow::before {
          content: "";
          width: 23px;
          height: 2px;
          background: currentColor;
        }

        .tk-about-hero {
          position: relative;
          min-height: 590px;
          display: flex;
          align-items: center;
          color: white;
          background:
            radial-gradient(ellipse at 78% 45%, rgba(37,99,235,.28), transparent 38%),
            radial-gradient(ellipse at 15% 100%, rgba(14,165,233,.13), transparent 35%),
            linear-gradient(115deg, #07152b 0%, #0b1e3b 58%, #102b50 100%);
          isolation: isolate;
        }

        .tk-about-hero::before {
          content: "";
          position: absolute;
          inset: 0;
          z-index: -1;
          opacity: .16;
          background-image:
            linear-gradient(rgba(255,255,255,.12) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.12) 1px, transparent 1px);
          background-size: 54px 54px;
          mask-image: linear-gradient(to right, black, transparent 85%);
        }

        .tk-hero-content {
          position: relative;
          z-index: 2;
          max-width: 680px;
          padding: 100px 0 110px;
        }

        .tk-hero-tag {
          display: inline-flex;
          align-items: center;
          gap: 9px;
          padding: 9px 14px;
          border: 1px solid rgba(147,197,253,.28);
          border-radius: 30px;
          color: #bfdbfe;
          background: rgba(37,99,235,.12);
          font-size: 12px;
          font-weight: 700;
          letter-spacing: 1.2px;
          text-transform: uppercase;
        }

        .tk-hero-tag svg { color: #60a5fa; }

        .tk-hero-content h1 {
          margin: 27px 0 22px;
          max-width: 690px;
          color: #fff;
          font-size: clamp(43px, 6vw, 70px);
          line-height: 1.06;
          letter-spacing: -3px;
          font-weight: 800;
        }

        .tk-hero-content h1 span {
          color: #75a9ff;
        }

        .tk-hero-content > p {
          max-width: 570px;
          color: #bdcce0;
          font-size: 17px;
          line-height: 1.85;
          margin: 0;
        }

        .tk-hero-actions {
          display: flex;
          flex-wrap: wrap;
          gap: 13px;
          margin-top: 34px;
        }

        .tk-btn {
          min-height: 49px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
          padding: 0 21px;
          border-radius: 9px;
          font-size: 14px;
          font-weight: 750;
          transition: .2s ease;
        }

        .tk-btn-primary {
          color: white;
          background: #2563eb;
          box-shadow: 0 9px 24px rgba(37,99,235,.25);
        }

        .tk-btn-primary:hover {
          background: #1d4ed8;
          transform: translateY(-2px);
        }

        .tk-btn-outline {
          color: white;
          border: 1px solid rgba(255,255,255,.25);
          background: rgba(255,255,255,.05);
        }

        .tk-btn-outline:hover {
          background: rgba(255,255,255,.12);
        }

        .tk-hero-art {
          position: absolute;
          width: 440px;
          height: 440px;
          right: max(5%, calc((100vw - 1160px) / 2));
          top: 50%;
          transform: translateY(-50%);
          display: grid;
          place-items: center;
          pointer-events: none;
        }

        .tk-orbit {
          position: absolute;
          width: 370px;
          height: 370px;
          border: 1px solid rgba(147,197,253,.18);
          border-radius: 50%;
        }

        .tk-orbit-two {
          width: 270px;
          height: 270px;
          border-color: rgba(147,197,253,.25);
        }

        .tk-orbit-three {
          width: 165px;
          height: 165px;
          border-color: rgba(147,197,253,.32);
        }

        .tk-orbit::before,
        .tk-orbit-two::before {
          content: "";
          position: absolute;
          width: 9px;
          height: 9px;
          top: 17%;
          right: 17%;
          border-radius: 50%;
          background: #60a5fa;
          box-shadow: 0 0 20px #60a5fa;
        }

        .tk-globe {
          width: 112px;
          height: 112px;
          display: grid;
          place-items: center;
          color: #bfdbfe;
          border: 1px solid rgba(147,197,253,.35);
          border-radius: 30px;
          background: linear-gradient(145deg, rgba(37,99,235,.32), rgba(255,255,255,.06));
          box-shadow: 0 0 80px rgba(37,99,235,.25);
        }

        .tk-floating-label {
          position: absolute;
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 12px 15px;
          color: #eaf2ff;
          border: 1px solid rgba(147,197,253,.2);
          border-radius: 12px;
          background: rgba(11,30,59,.88);
          box-shadow: 0 15px 40px rgba(0,0,0,.2);
          font-size: 12px;
          font-weight: 700;
          white-space: nowrap;
        }

        .tk-floating-label svg { color: #60a5fa; }
        .tk-float-one { top: 70px; left: 4px; }
        .tk-float-two { right: 0; top: 190px; }
        .tk-float-three { bottom: 65px; left: 18px; }

        .tk-intro {
          padding: 105px 0;
          background: #fff;
        }

        .tk-intro-grid {
          display: grid;
          grid-template-columns: .85fr 1.15fr;
          gap: 90px;
          align-items: start;
        }

        .tk-intro-heading h2 {
          margin: 18px 0 0;
          color: var(--tk-navy);
          font-size: clamp(32px, 4vw, 46px);
          line-height: 1.15;
          letter-spacing: -1.8px;
        }

        .tk-intro-copy p {
          margin: 0 0 18px;
          color: #64748b;
          font-size: 16px;
          line-height: 1.9;
        }

        .tk-intro-copy p strong { color: #172d4c; }

        .tk-section {
          padding: 100px 0;
        }

        .tk-section-soft { background: #f6f8fc; }

        .tk-section-heading {
          max-width: 660px;
          margin-bottom: 48px;
        }

        .tk-section-heading h2 {
          margin: 17px 0 14px;
          color: var(--tk-navy);
          font-size: clamp(32px, 4vw, 45px);
          line-height: 1.15;
          letter-spacing: -1.8px;
        }

        .tk-section-heading p {
          margin: 0;
          color: var(--tk-muted);
          font-size: 16px;
          line-height: 1.8;
        }

        .tk-service-grid {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 17px;
        }

        .tk-service-card {
          min-height: 280px;
          padding: 27px 23px;
          border: 1px solid var(--tk-line);
          border-radius: 15px;
          background: #fff;
          transition: .25s ease;
        }

        .tk-service-card:hover {
          transform: translateY(-5px);
          border-color: #b8cdfb;
          box-shadow: 0 18px 40px rgba(15,35,70,.08);
        }

        .tk-service-top {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 28px;
        }

        .tk-service-icon {
          width: 48px;
          height: 48px;
          display: grid;
          place-items: center;
          color: #2563eb;
          border-radius: 12px;
          background: #edf4ff;
        }

        .tk-service-number {
          color: #a3afc1;
          font-size: 12px;
          font-weight: 800;
          letter-spacing: 1px;
        }

        .tk-service-card h3 {
          margin: 0 0 12px;
          color: #132744;
          font-size: 18px;
          letter-spacing: -.3px;
        }

        .tk-service-card p {
          margin: 0;
          color: #718096;
          font-size: 14px;
          line-height: 1.8;
        }

        .tk-global {
          position: relative;
          overflow: hidden;
          padding: 100px 0;
          color: #fff;
          background:
            radial-gradient(circle at 80% 50%, rgba(37,99,235,.25), transparent 32%),
            linear-gradient(115deg, #08172e, #10294b);
        }

        .tk-global-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 80px;
          align-items: center;
        }

        .tk-global .tk-eyebrow { color: #93c5fd; }

        .tk-global h2 {
          margin: 18px 0;
          color: white;
          font-size: clamp(34px, 4vw, 47px);
          line-height: 1.15;
          letter-spacing: -1.8px;
        }

        .tk-global-copy > p {
          color: #bdcce0;
          font-size: 16px;
          line-height: 1.85;
        }

        .tk-market-list {
          display: flex;
          flex-wrap: wrap;
          gap: 12px;
          margin-top: 28px;
        }

        .tk-market {
          display: inline-flex;
          align-items: center;
          gap: 9px;
          padding: 12px 15px;
          border: 1px solid rgba(147,197,253,.22);
          border-radius: 9px;
          color: #e5efff;
          background: rgba(255,255,255,.05);
          font-size: 13px;
          font-weight: 700;
        }

        .tk-market svg { color: #60a5fa; }

        .tk-global-note {
          margin-top: 18px;
          color: #8fa6c3;
          font-size: 12px;
          line-height: 1.7;
        }

        .tk-global-panel {
          position: relative;
          padding: 34px;
          border: 1px solid rgba(147,197,253,.18);
          border-radius: 20px;
          background: rgba(255,255,255,.055);
        }

        .tk-global-panel h3 {
          margin: 0 0 23px;
          color: #fff;
          font-size: 18px;
        }

        .tk-global-point {
          display: flex;
          gap: 15px;
          padding: 17px 0;
          border-top: 1px solid rgba(255,255,255,.11);
        }

        .tk-global-point-icon {
          flex: 0 0 40px;
          width: 40px;
          height: 40px;
          display: grid;
          place-items: center;
          color: #93c5fd;
          border-radius: 11px;
          background: rgba(37,99,235,.2);
        }

        .tk-global-point strong {
          display: block;
          margin-bottom: 5px;
          color: #fff;
          font-size: 14px;
        }

        .tk-global-point span {
          display: block;
          color: #a9bad0;
          font-size: 13px;
          line-height: 1.6;
        }

        .tk-purpose-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
        }

        .tk-purpose-card {
          padding: 35px;
          border: 1px solid var(--tk-line);
          border-radius: 16px;
          background: #fff;
        }

        .tk-purpose-card > div {
          width: 48px;
          height: 48px;
          display: grid;
          place-items: center;
          color: #2563eb;
          border-radius: 12px;
          background: #edf4ff;
        }

        .tk-purpose-card h3 {
          margin: 22px 0 11px;
          color: #132744;
          font-size: 22px;
        }

        .tk-purpose-card p {
          margin: 0;
          color: #718096;
          font-size: 15px;
          line-height: 1.85;
        }

        .tk-values-grid {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 25px;
        }

        .tk-value-card { padding: 10px 4px; }

        .tk-value-icon {
          width: 47px;
          height: 47px;
          display: grid;
          place-items: center;
          color: #2563eb;
          border-radius: 13px;
          background: #edf4ff;
        }

        .tk-value-card h3 {
          margin: 19px 0 9px;
          color: #172d4c;
          font-size: 17px;
        }

        .tk-value-card p {
          margin: 0;
          color: #718096;
          font-size: 14px;
          line-height: 1.8;
        }

        .tk-about-cta {
          padding: 82px 24px;
          color: white;
          text-align: center;
          background:
            radial-gradient(ellipse at 50% 0%, rgba(59,130,246,.28), transparent 60%),
            #07152b;
        }

        .tk-about-cta .tk-eyebrow { color: #93c5fd; }

        .tk-about-cta h2 {
          max-width: 700px;
          margin: 18px auto 14px;
          color: #fff;
          font-size: clamp(32px, 4vw, 47px);
          letter-spacing: -1.8px;
          line-height: 1.15;
        }

        .tk-about-cta p {
          max-width: 560px;
          margin: 0 auto;
          color: #bdcce0;
          font-size: 16px;
          line-height: 1.8;
        }

        .tk-about-cta .tk-hero-actions {
          justify-content: center;
          margin-top: 29px;
        }

        @media (max-width: 1050px) {
          .tk-hero-art {
            right: -70px;
            opacity: .55;
          }
          .tk-service-grid,
          .tk-values-grid {
            grid-template-columns: repeat(2, 1fr);
          }
          .tk-intro-grid { gap: 45px; }
        }

        @media (max-width: 720px) {
          .tk-container { width: min(100% - 36px, 560px); }
          .tk-about-hero { min-height: 570px; }
          .tk-hero-content {
            padding: 85px 0;
            max-width: 100%;
          }
          .tk-hero-content h1 {
            font-size: clamp(42px, 11vw, 58px);
            letter-spacing: -2px;
          }
          .tk-hero-content > p { font-size: 15px; }
          .tk-hero-art {
            right: -180px;
            top: 58%;
            opacity: .22;
          }
          .tk-intro, .tk-section, .tk-global { padding: 72px 0; }
          .tk-intro-grid,
          .tk-global-grid,
          .tk-purpose-grid {
            grid-template-columns: 1fr;
            gap: 30px;
          }
          .tk-service-grid { grid-template-columns: 1fr 1fr; }
          .tk-service-card { min-height: 0; padding: 22px 18px; }
          .tk-values-grid { grid-template-columns: 1fr 1fr; gap: 30px 20px; }
          .tk-global-panel { padding: 25px 21px; }
        }

        @media (max-width: 440px) {
          .tk-service-grid,
          .tk-values-grid { grid-template-columns: 1fr; }
          .tk-service-card { padding: 25px; }
          .tk-hero-actions { flex-direction: column; align-items: stretch; }
          .tk-btn { width: 100%; }
          .tk-purpose-card { padding: 27px; }
          .tk-about-cta { padding: 70px 20px; }
        }
      `}</style>

      {/* Hero */}
      <section className="tk-about-hero">
        <div className="tk-container">
          <div className="tk-hero-content">
            <div className="tk-hero-tag">
              <Sparkles size={15} />
              About TEKVITO
            </div>

            <h1>
              Connecting talent.
              <br />
              <span>Building possibilities.</span>
            </h1>

            <p>
              TEKVITO is a global IT staffing and recruitment company
              focused on connecting businesses with technology talent
              through professional recruitment and trusted partnerships.
            </p>

            <div className="tk-hero-actions">
              <Link to="/jobs" className="tk-btn tk-btn-primary">
                Explore Opportunities <ArrowRight size={17} />
              </Link>
              <Link to="/hire" className="tk-btn tk-btn-outline">
                Work With Us <ArrowRight size={17} />
              </Link>
            </div>
          </div>
        </div>

        <div className="tk-hero-art" aria-hidden="true">
          <div className="tk-orbit" />
          <div className="tk-orbit tk-orbit-two" />
          <div className="tk-orbit tk-orbit-three" />
          <div className="tk-globe"><Globe2 size={58} strokeWidth={1.2} /></div>
          <div className="tk-floating-label tk-float-one">
            <Users size={17} /> Global Talent
          </div>
          <div className="tk-floating-label tk-float-two">
            <Building2 size={17} /> Business Connections
          </div>
          <div className="tk-floating-label tk-float-three">
            <Network size={17} /> Recruitment Network
          </div>
        </div>
      </section>

      {/* Introduction */}
      <section className="tk-intro">
        <div className="tk-container tk-intro-grid">
          <div className="tk-intro-heading">
            <div className="tk-eyebrow">Who we are</div>
            <h2>People and technology. Connected with purpose.</h2>
          </div>

          <div className="tk-intro-copy">
            <p>
              <strong>TEKVITO LLC</strong> works at the intersection of
              technology, talent and business. We help organizations address
              their hiring needs while helping professionals discover
              opportunities that align with their skills and experience.
            </p>
            <p>
              Our approach brings together direct client relationships,
              recruitment partnerships and talent networks. By creating
              clear channels between businesses and professionals, we aim
              to make recruitment more organized, accessible and effective.
            </p>
            <p>
              We believe successful recruitment is more than filling a
              vacancy. It is about building professional relationships
              that can create lasting value.
            </p>
          </div>
        </div>
      </section>

      {/* Services */}
      <section className="tk-section tk-section-soft">
        <div className="tk-container">
          <div className="tk-section-heading">
            <div className="tk-eyebrow">What we do</div>
            <h2>Recruitment solutions built around real business needs.</h2>
            <p>
              We support different hiring models and work with businesses
              and recruitment partners to connect the right talent with
              the right requirements.
            </p>
          </div>

          <div className="tk-service-grid">
            {services.map((service) => {
              const Icon = service.icon
              return (
                <article className="tk-service-card" key={service.number}>
                  <div className="tk-service-top">
                    <div className="tk-service-icon"><Icon size={23} /></div>
                    <span className="tk-service-number">{service.number}</span>
                  </div>
                  <h3>{service.title}</h3>
                  <p>{service.description}</p>
                </article>
              )
            })}
          </div>
        </div>
      </section>

      {/* Global reach */}
      <section className="tk-global">
        <div className="tk-container tk-global-grid">
          <div className="tk-global-copy">
            <div className="tk-eyebrow">Global perspective</div>
            <h2>Talent has no borders. Neither should opportunity.</h2>
            <p>
              TEKVITO is building its recruitment network with an
              international outlook. Our target markets include key
              business and technology markets where skilled professionals
              and organizations can create meaningful connections.
            </p>

            <div className="tk-market-list">
              <div className="tk-market"><Globe2 size={16} /> London</div>
              <div className="tk-market"><Globe2 size={16} /> United States</div>
              <div className="tk-market"><Globe2 size={16} /> Japan</div>
            </div>

            <div className="tk-global-note">
              Target markets for business development and recruitment
              partnerships. These are not representations of physical office locations.
            </div>
          </div>

          <div className="tk-global-panel">
            <h3>How we build connections</h3>

            <div className="tk-global-point">
              <div className="tk-global-point-icon"><Building2 size={20} /></div>
              <div>
                <strong>Understand business needs</strong>
                <span>Work with organizations to understand roles, skills and hiring requirements.</span>
              </div>
            </div>

            <div className="tk-global-point">
              <div className="tk-global-point-icon"><Users size={20} /></div>
              <div>
                <strong>Connect talent networks</strong>
                <span>Engage with professionals and recruitment partners to identify relevant talent.</span>
              </div>
            </div>

            <div className="tk-global-point">
              <div className="tk-global-point-icon"><Handshake size={20} /></div>
              <div>
                <strong>Support lasting partnerships</strong>
                <span>Encourage clear communication and professional collaboration throughout recruitment.</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Mission and vision */}
      <section className="tk-section tk-section-soft">
        <div className="tk-container">
          <div className="tk-section-heading">
            <div className="tk-eyebrow">Our direction</div>
            <h2>What drives us forward.</h2>
            <p>
              Our purpose shapes how we work today and the kind of
              recruitment network we want to build for tomorrow.
            </p>
          </div>

          <div className="tk-purpose-grid">
            <article className="tk-purpose-card">
              <div><Target size={24} /></div>
              <h3>Our Mission</h3>
              <p>
                To connect businesses and technology professionals through
                transparent recruitment practices, meaningful partnerships
                and a people-focused approach.
              </p>
            </article>

            <article className="tk-purpose-card">
              <div><Compass size={24} /></div>
              <h3>Our Vision</h3>
              <p>
                To build a trusted international recruitment network that
                makes access to talent and professional opportunities
                more connected and effective.
              </p>
            </article>
          </div>
        </div>
      </section>

      {/* Values */}
      <section className="tk-section">
        <div className="tk-container">
          <div className="tk-section-heading">
            <div className="tk-eyebrow">Our principles</div>
            <h2>The values behind every connection.</h2>
            <p>
              The way we work matters just as much as the results
              we aim to achieve.
            </p>
          </div>

          <div className="tk-values-grid">
            {values.map((value) => {
              const Icon = value.icon
              return (
                <article className="tk-value-card" key={value.title}>
                  <div className="tk-value-icon"><Icon size={22} /></div>
                  <h3>{value.title}</h3>
                  <p>{value.description}</p>
                </article>
              )
            })}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="tk-about-cta">
        <div className="tk-eyebrow">Let's connect</div>
        <h2>Let's build better opportunities together.</h2>
        <p>
          Whether you are looking for your next technology opportunity
          or seeking talent for your business, start a conversation with TEKVITO.
        </p>

        <div className="tk-hero-actions">
          <Link to="/jobs" className="tk-btn tk-btn-primary">
            Explore Jobs <ArrowRight size={17} />
          </Link>
          <Link to="/hire" className="tk-btn tk-btn-outline">
            Partner With Us <ArrowRight size={17} />
          </Link>
        </div>
      </section>
    </main>
  )
}
