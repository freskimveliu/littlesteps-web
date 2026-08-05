<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import LegalPage from '@/components/legal/LegalPage.vue';
import LegalSection from '@/components/legal/LegalSection.vue';
import { LEGAL } from '@/support/legal';
</script>

<template>
    <LegalPage
        current="terms"
        title="Terms & Conditions"
        eyebrow="The agreement"
        icon="shield-checkmark"
        :updated="LEGAL.termsUpdated"
        intro="LittleSteps holds something you cannot re-download, so it is worth being plain about who owes what to whom. This is the whole agreement, written to be read rather than skimmed."
        description="The terms of use for LittleSteps: your account, your memories, what we promise, and what we ask of you."
    >
        <LegalSection id="agreement" :index="1" heading="Who this agreement is between">
            <p>
                These terms are between you and <strong>{{ LEGAL.entity }}</strong> ("we", "us"), and they cover the
                LittleSteps mobile app, this website, and everything either of them connects to. Installing the app,
                creating an account, or using the service at all means you accept them.
            </p>
            <p>
                If you do not accept them, the app is not for you, and you can remove it. Nothing here takes away rights
                the law where you live gives you and does not let you sign away.
            </p>
        </LegalSection>

        <LegalSection id="account" :index="2" heading="Your account">
            <p>
                LittleSteps creates an account for you the first time you open the app, before you have typed anything.
                That account is what your child's map hangs off. It exists so you can start immediately, not so we can
                learn who you are.
            </p>
            <ul>
                <li>
                    <strong>Adding an email and password</strong> turns that account into one you can sign back into on
                    another phone. Until you do, the account lives only on the handset it was made on, and losing the
                    handset loses the memories with it.
                </li>
                <li>
                    <strong>You must be at least {{ LEGAL.minimumAge }}</strong> to hold an account, and you must be a
                    parent, guardian, or someone a parent has invited, of every child you add.
                </li>
                <li>
                    <strong>Your password is yours to keep safe.</strong> Anything done from an account signed in with
                    your credentials is treated as done by you.
                </li>
                <li>
                    <strong>One account per person.</strong> Do not share credentials — share codes exist precisely so
                    you do not have to.
                </li>
            </ul>
        </LegalSection>

        <LegalSection id="service" :index="3" heading="What LittleSteps does">
            <p>
                LittleSteps gives each child a guided map of their first six years: chapters that open with their age,
                milestones written to arrive at roughly the right time, a daily prompt, and XP, levels, trophies and
                streaks layered on top to make showing up worth it.
            </p>
            <p>
                The map is a suggestion, not a schedule. Milestones can be renamed, moved, removed, or written from
                scratch. Nothing on it is medical advice, developmental assessment, or a benchmark your child is
                expected to meet — if you are worried about how your child is growing, speak to a doctor, not to an app.
            </p>
        </LegalSection>

        <LegalSection id="content" :index="4" heading="Your memories stay yours">
            <p>
                Every photo, sentence, measurement and mood you record belongs to you. We claim no ownership of it and
                we do not use it to train anything, sell anything, or show anybody an advert.
            </p>
            <p>
                To actually run the service we need a narrow, practical permission: the right to store your content,
                back it up, resize images, and show it back to you and to the people you have invited to that child.
                That permission exists only so the app can work, it is not exclusive, and it ends when you delete the
                content or close your account.
            </p>
            <p>
                You are responsible for what you put in. By uploading something you confirm you have the right to
                upload it, and that anybody recognisable in a photo is someone you may reasonably share on their behalf.
            </p>
        </LegalSection>

        <LegalSection id="family" :index="5" heading="Bringing family in">
            <p>
                Every account has a six-character share code. Handing it to a grandparent lets you add them to a child's
                map. You decide who joins, what they may do, and you can remove them at any time.
            </p>
            <ul>
                <li>Whoever created a child owns that child's record — only they can rename or delete it.</li>
                <li>Members you invite see the memories on that child's map, and read-only members cannot change them.</li>
                <li>
                    Treat your share code like a house key. Anyone who has it can ask to be let in, so give it out
                    deliberately and remove people you no longer want on the journey.
                </li>
            </ul>
        </LegalSection>

        <LegalSection id="use" :index="6" heading="What we ask you not to do">
            <p>Do not use LittleSteps to:</p>
            <ul>
                <li>store anything unlawful, or any image of a child that is not yours to hold;</li>
                <li>impersonate somebody else, or claim a child you have no relationship to;</li>
                <li>break into another account, another family's map, or any part of our infrastructure;</li>
                <li>scrape, resell, or rebuild the service, or hammer the API beyond ordinary use of the app;</li>
                <li>upload malware, or anything intended to break the service for other families.</li>
            </ul>
            <p>
                We may suspend or close an account that does any of these, and where the law requires it we will report
                what we find.
            </p>
        </LegalSection>

        <LegalSection id="price" :index="7" heading="What it costs">
            <p>
                Starting costs nothing. Adding a child, following the map and capturing a memory a day are free, and the
                daily limits described in the app are part of the free service by design rather than a paywall.
            </p>
            <p>
                If a paid tier arrives later it will be for extras, and we will not put memories you have already kept
                behind it. Any price, billing period and cancellation terms will be shown to you before you are charged,
                and payment will run through the App Store or Google Play under their rules.
            </p>
        </LegalSection>

        <LegalSection id="availability" :index="8" heading="Availability, changes and the odd broken thing">
            <p>
                We work to keep LittleSteps running and your memories intact, but we cannot promise the service will
                never be down, never lose a request, or never carry a bug. Features may change, be added, or be
                withdrawn as the app grows.
            </p>
            <p>
                <strong>Keep your own copies of anything irreplaceable.</strong> LittleSteps is a keepsake, not a backup
                service, and the original photos should stay on your phone or in your own photo library too.
            </p>
        </LegalSection>

        <LegalSection id="closing" :index="9" heading="Closing your account">
            <p>
                You can delete your account from the Profile screen in the app at any time, and you do not have to ask
                us first. Deleting it removes your account, your children's records, and the memories, photos and
                measurements attached to them.
            </p>
            <p>
                That is deliberate and it is permanent. Export or save anything you want to keep before you tap it. We
                may also close an account that breaches these terms, and we will tell you why when we do.
            </p>
        </LegalSection>

        <LegalSection id="liability" :index="10" heading="Where our responsibility ends">
            <p>
                LittleSteps is provided as it is. To the fullest extent the law allows, we are not liable for lost
                content, lost profits, or indirect or consequential loss, and our total liability to you for any claim
                connected to the service is limited to what you have paid us for it in the twelve months before the
                claim.
            </p>
            <p>
                Nothing in these terms limits our liability for death or personal injury caused by our negligence, for
                fraud, or for anything else that cannot lawfully be limited.
            </p>
        </LegalSection>

        <LegalSection id="changes" :index="11" heading="Changes to these terms">
            <p>
                We may update these terms as the app changes or the law does. The date at the top of this page always
                shows the current version, and if a change materially affects your rights we will tell you in the app or
                by email before it takes effect.
            </p>
            <p>Carrying on using LittleSteps after a change means you accept the updated terms.</p>
        </LegalSection>

        <LegalSection id="law" :index="12" heading="Law, disputes and getting hold of us">
            <p>
                These terms are governed by the law of {{ LEGAL.jurisdiction }}, and its courts have jurisdiction over
                any dispute — without taking away any protection the law of the country you live in gives you as a
                consumer.
            </p>
            <p>
                Most things are settled fastest by writing to us first at
                <a :href="`mailto:${LEGAL.contactEmail}`">{{ LEGAL.contactEmail }}</a>. How we handle your data is set
                out separately in the <Link href="/privacy">Privacy Policy</Link>, which forms part of this agreement.
            </p>
        </LegalSection>
    </LegalPage>
</template>
