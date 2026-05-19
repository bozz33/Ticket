"use client";

import type { PublicContent } from "@/lib/types";

import { ApplicationActions } from "./call-for-project-application/ApplicationActions";
import { ApplicationPaymentOptions } from "./call-for-project-application/ApplicationPaymentOptions";
import { ApplicationProgress } from "./call-for-project-application/ApplicationProgress";
import { ApplicationStepPanel } from "./call-for-project-application/ApplicationStepPanel";
import { ApplicationSummary } from "./call-for-project-application/ApplicationSummary";
import { DynamicCallForProjectApplicationForm } from "./call-for-project-application/DynamicCallForProjectApplicationForm";
import { useApplicationFormController } from "./call-for-project-application/useApplicationFormController";

export function CallForProjectApplicationForm({ item }: { item: PublicContent }) {
  if (item.dynamicForm) {
    return <DynamicCallForProjectApplicationForm item={item} />;
  }

  return <LegacyCallForProjectApplicationForm item={item} />;
}

function LegacyCallForProjectApplicationForm({ item }: { item: PublicContent }) {
  const {
    currentStep,
    currentStepCompletedCount,
    currentStepIndex,
    currentStepRequiredCount,
    form,
    handleSubmit,
    isLastStep,
    overallCompletedCount,
    overallCompletionPercentage,
    overallRequiredCount,
    renderField,
    setCurrentStepIndex,
    submitError,
    submitSuccess,
    submitting,
    visibleFields,
    wizardSteps,
    goToNextStep,
    goToPreviousStep,
  } = useApplicationFormController(item);

  if (!form) {
    return null;
  }

  return (
    <div className="checkout-layout">
      <div className="checkout-form">
        <div className="checkout-head">
          <div>
            <span className="badge">Candidature publique</span>
            <h2>{form.title}</h2>
            <p className="section-copy">
              {form.description ?? "Renseignez soigneusement les informations requises avant l'envoi."}
            </p>
          </div>
        </div>

        <ApplicationPaymentOptions form={form} item={item} />

        <form className="contact-form-group" onSubmit={handleSubmit}>
          <ApplicationProgress
            currentStepIndex={currentStepIndex}
            overallCompletedCount={overallCompletedCount}
            overallCompletionPercentage={overallCompletionPercentage}
            overallRequiredCount={overallRequiredCount}
            onStepSelect={setCurrentStepIndex}
            visibleFieldCount={visibleFields.length}
            wizardSteps={wizardSteps}
          />

          <ApplicationStepPanel
            currentStep={currentStep}
            currentStepCompletedCount={currentStepCompletedCount}
            currentStepIndex={currentStepIndex}
            currentStepRequiredCount={currentStepRequiredCount}
            renderField={renderField}
            wizardStepCount={wizardSteps.length}
          />

          {submitError ? <p style={{ color: "#b91c1c", margin: 0 }}>{submitError}</p> : null}
          {submitSuccess ? <p style={{ color: "#15803d", margin: 0 }}>{submitSuccess}</p> : null}

          <ApplicationActions
            currentStepIndex={currentStepIndex}
            form={form}
            isLastStep={isLastStep}
            onNext={goToNextStep}
            onPrevious={goToPreviousStep}
            submitting={submitting}
          />
        </form>
      </div>

      <ApplicationSummary item={item} />
    </div>
  );
}
