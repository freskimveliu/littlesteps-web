<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import LegalPage from '@/components/legal/LegalPage.vue';
import LegalSection from '@/components/legal/LegalSection.vue';
import { LEGAL } from '@/support/legal';
</script>

<template>
    <LegalPage
        current="privacy"
        title="Privacy Policy"
        eyebrow="Your data"
        icon="lock-closed"
        :updated="LEGAL.privacyUpdated"
        intro="LittleSteps holds photographs of children and the small private details of a family's life. That is about as sensitive as data gets, so here is exactly what we keep, why we keep it, and how you get rid of it."
        description="How LittleSteps collects, uses, stores and deletes your data and your child's — written plainly, with no tracking and no advertising."
    >
        <LegalSection id="responsible" :index="1" heading="Who is responsible for your data">
            <p>
                <strong>{{ LEGAL.entity }}</strong> is the data controller for everything described here. For any
                question about your data, or to exercise any of the rights below, write to
                <a :href="`mailto:${LEGAL.privacyEmail}`">{{ LEGAL.privacyEmail }}</a>.
            </p>
        </LegalSection>

        <LegalSection id="summary" :index="2" heading="The short version">
            <ul>
                <li>We do not sell your data, and we never will.</li>
                <li>There are no adverts in LittleSteps, and no advertising or analytics trackers in the app.</li>
                <li>Nothing you record is used to train a model or profile you.</li>
                <li>Nothing is public. A memory is visible to you and to the people you invite to that child, full stop.</li>
                <li>Deleting your account really deletes it, and you can do that yourself from the app.</li>
            </ul>
        </LegalSection>

        <LegalSection id="collect" :index="3" heading="What we collect">
            <p><strong>Your account.</strong> Your name, and your email address and password if you create a full account — the password is stored only as a one-way hash, so nobody at our end can read it. We also keep your language, your time zone, your six-character share code, and your streak counts.</p>
            <p><strong>Your children.</strong> The name, birthday and gender you enter for each child, and their photo if you add one.</p>
            <p><strong>Your memories.</strong> Everything you record against a milestone or a day: the text you write, the photos you attach, the mood you pick, the date, and any measurements you log inside a milestone — weight, height, teeth, shoe size and similar. <strong>Measurements about your child are health-related data</strong>, and we treat them as the most sensitive thing in the app.</p>
            <p><strong>Your family.</strong> Who you have invited to each child's map, the relation you gave them, and whether they may edit or only read.</p>
            <p><strong>Your device.</strong> If you turn notifications on, we store a push token for that handset along with its platform, so a reminder can reach it. Turning notifications off, or signing out, removes it.</p>
            <p><strong>Technical records.</strong> Our servers keep ordinary request logs — IP address, time, and which endpoint was called — for security and debugging. We do not build a profile from them.</p>
            <p>
                We do not ask for your location, your contacts, or your camera roll as a whole. The app asks for photo
                access only when you pick a picture, and only takes the picture you picked.
            </p>
        </LegalSection>

        <LegalSection id="why" :index="4" heading="Why we are allowed to hold it">
            <ul>
                <li>
                    <strong>To give you the service you asked for.</strong> Your account, your children's maps and your
                    memories exist because you asked us to keep them. Without them there is no app.
                </li>
                <li>
                    <strong>With your consent.</strong> Photographs and health-related measurements are kept on the
                    basis of your consent, which you give by recording them and can withdraw at any time by deleting
                    them. Push notifications are sent only while you leave the switch on.
                </li>
                <li>
                    <strong>Because we have a legitimate interest.</strong> Keeping the service secure, stopping abuse,
                    and fixing what breaks.
                </li>
                <li><strong>Because the law requires it,</strong> where it does.</li>
            </ul>
        </LegalSection>

        <LegalSection id="children" :index="5" heading="Data about children">
            <p>
                LittleSteps is used by adults to record their own children. Children do not hold accounts, and the app
                is not marketed to them. Everything about a child is entered by a parent or by somebody that parent
                invited.
            </p>
            <p>
                Because of that, a child's data is never used for profiling, never used for advertising, never used for
                training, and never shared beyond the people you invite. When the child is old enough to ask for it, the
                account holder can hand the whole record over or delete it — the rights below apply to the child's data
                as much as to yours.
            </p>
        </LegalSection>

        <LegalSection id="sharing" :index="6" heading="Who else can see it">
            <p><strong>People you invite.</strong> Anybody you add to a child sees that child's memories. You choose them, and you can remove them.</p>
            <p><strong>The companies that run our infrastructure.</strong> We use Amazon Web Services to host the service and store photos. They process data on our instructions only, under a contract, and cannot use it for anything of their own.</p>
            <p><strong>Apple and Google,</strong> if you turn notifications on — a push notification has to travel through their delivery services to reach a handset.</p>
            <p>
                That is the whole list. We do not share your data with advertisers, data brokers, or analytics
                companies. If we are ever legally compelled to hand something over we will tell you, unless the law
                forbids us from doing so.
            </p>
        </LegalSection>

        <LegalSection id="where" :index="7" heading="Where it is kept">
            <p>
                Your data lives on servers in the European Union — Amazon Web Services' Frankfurt region — and photos
                are stored in the same place. Where anything has to cross a border, it is covered by the standard
                contractual clauses the European Commission approves for that purpose.
            </p>
        </LegalSection>

        <LegalSection id="retention" :index="8" heading="How long we keep it">
            <p>
                Your memories stay until you remove them. That is the point of the app — a record you can still read in
                fifteen years is no use if we quietly age it out.
            </p>
            <ul>
                <li>Delete a memory or a photo and it goes, along with the file behind it.</li>
                <li>Delete a child and their whole map goes with them.</li>
                <li>Delete your account and everything above goes, together with the account itself.</li>
                <li>Technical logs are kept for a short period for security, then discarded.</li>
                <li>Where the law requires us to keep a record for longer, we keep only that record and nothing else.</li>
            </ul>
        </LegalSection>

        <LegalSection id="security" :index="9" heading="How it is kept safe">
            <ul>
                <li>Everything travels over an encrypted connection.</li>
                <li>Passwords are stored as one-way hashes, never as text anybody could read.</li>
                <li>Photos are not on a public URL — the app fetches them through links that expire.</li>
                <li>Access to a child's map is checked on the server on every request, not hidden in the app.</li>
                <li>Only the people who need it can reach production data, and that access is logged.</li>
            </ul>
            <p>
                No system is perfect. If a breach ever puts your data at risk we will tell you and the relevant
                authority as quickly as the law requires, and we will tell you plainly what happened.
            </p>
        </LegalSection>

        <LegalSection id="rights" :index="10" heading="Your rights">
            <p>You can, at any time:</p>
            <ul>
                <li><strong>See what we hold</strong> — most of it is already visible in the app; ask us for the rest.</li>
                <li><strong>Correct it</strong> — names, birthdays, measurements and text are all editable in the app.</li>
                <li><strong>Delete it</strong> — a memory, a child, or the whole account, from the Profile screen.</li>
                <li><strong>Get a copy</strong> — ask and we will send your data in a portable format.</li>
                <li><strong>Withdraw consent</strong> — turn notifications off, or delete the photos and measurements you no longer want kept.</li>
                <li><strong>Object or ask us to restrict processing</strong> — tell us and we will act on it.</li>
                <li><strong>Complain</strong> — to us first, and to your national data protection authority if we have not put it right.</li>
            </ul>
            <p>
                Write to <a :href="`mailto:${LEGAL.privacyEmail}`">{{ LEGAL.privacyEmail }}</a> and we will answer within
                a month. We do not charge for any of this.
            </p>
        </LegalSection>

        <LegalSection id="cookies" :index="11" heading="Cookies and this website">
            <p>
                The app uses no cookies at all. This website sets one session cookie, and it is used only to keep an
                administrator signed in to the admin area. There are no analytics cookies, no advertising cookies, and
                no third-party trackers on any page.
            </p>
        </LegalSection>

        <LegalSection id="changes" :index="12" heading="Changes and how to reach us">
            <p>
                If this policy changes, the date at the top of the page changes with it, and anything that materially
                affects how your data is handled will be announced in the app or by email before it takes effect.
            </p>
            <p>
                For anything at all about your data, write to
                <a :href="`mailto:${LEGAL.privacyEmail}`">{{ LEGAL.privacyEmail }}</a>. The rules for using the service
                itself are in the <Link href="/terms">Terms &amp; Conditions</Link>.
            </p>
        </LegalSection>
    </LegalPage>
</template>
